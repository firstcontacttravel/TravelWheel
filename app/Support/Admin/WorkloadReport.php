<?php

namespace App\Support\Admin;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use App\Workflow\Deadlines;
use App\Workflow\WorkflowRegistry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The figures behind Insights → Workload: where bookings wait, which steps
 * run over, and how the work is spread across departments and people.
 *
 * Everything is read from work items and their history, so it covers every
 * service the same way. "Now" figures (open, overdue) ignore the period;
 * everything else counts what happened inside it.
 */
class WorkloadReport
{
    /** History is read this far before the period, so a step entered earlier still has its start. */
    private const LOOKBACK_DAYS = 90;

    public function __construct(
        private readonly WorkflowRegistry $registry,
        private readonly Deadlines $deadlines,
    ) {}

    /**
     * @return array{summary: array<string, mixed>, steps: list<array<string, mixed>>, departments: list<array<string, mixed>>, people: list<array<string, mixed>>}
     */
    public function build(CarbonInterface $from, CarbonInterface $to, ?string $service = null): array
    {
        return [
            'summary' => $this->summary($from, $to, $service),
            'steps' => $this->steps($from, $to, $service),
            'departments' => $this->departments($from, $to, $service),
            'people' => $this->people($from, $to, $service),
        ];
    }

    /** @return array<string, mixed> */
    private function summary(CarbonInterface $from, CarbonInterface $to, ?string $service): array
    {
        $completedIds = $this->completedIds($from, $to, $service);
        $completedCount = $completedIds->count();
        $completedLate = $completedCount > 0
            ? WorkItemEvent::query()->whereIn('work_item_id', $completedIds)->where('type', WorkItemEvent::DEADLINE_MISSED)->distinct()->count('work_item_id')
            : 0;

        $escalations = $this->escalations($service)->whereBetween('escalations.created_at', [$from, $to])->get(['escalations.*']);

        return [
            'open' => $this->items($service)->where('state', WorkItem::STATE_OPEN)->count(),
            'waiting' => $this->items($service)->where('state', WorkItem::STATE_WAITING)->count(),
            'overdue' => $this->items($service)->active()->where('due_at', '<', now())->count(),
            'unclaimed' => $this->items($service)->where('state', WorkItem::STATE_OPEN)->whereNull('owner_id')->count(),
            'completed' => $completedCount,
            'on_time_rate' => $completedCount > 0 ? round(100 * ($completedCount - $completedLate) / $completedCount, 1) : null,
            'missed' => $this->events($service)->where('work_item_events.type', WorkItemEvent::DEADLINE_MISSED)->whereBetween('work_item_events.created_at', [$from, $to])->count(),
            'escalations' => $escalations->count(),
            'escalation_hours' => $this->median($escalations->filter(fn (Escalation $e) => $e->closed_at && $e->status === Escalation::STATUS_RESOLVED)
                ->map(fn (Escalation $e) => $e->created_at->diffInMinutes($e->closed_at) / 60)),
        ];
    }

    /**
     * How long each step really takes: for every step a booking left inside
     * the period, the time from entering it to leaving it. Only steps where
     * staff act; time spent waiting on a customer is not anyone's backlog.
     *
     * @return list<array<string, mixed>>
     */
    private function steps(CarbonInterface $from, CarbonInterface $to, ?string $service): array
    {
        $events = $this->events($service)
            ->whereIn('work_item_events.type', [WorkItemEvent::CREATED, WorkItemEvent::STAGE_CHANGED])
            ->whereBetween('work_item_events.created_at', [$from->copy()->subDays(self::LOOKBACK_DAYS), $to])
            ->orderBy('work_item_events.created_at')->orderBy('work_item_events.id')
            ->get(['work_item_events.work_item_id', 'work_item_events.to', 'work_item_events.created_at', 'work_items.service']);

        $durations = [];
        foreach ($events->groupBy('work_item_id') as $history) {
            $history = $history->values();
            for ($i = 0; $i < $history->count() - 1; $i++) {
                $left = $history[$i + 1]->created_at;
                if ($left->lt($from) || $left->gt($to)) {
                    continue;
                }

                $key = $history[$i]->service.'|'.$history[$i]->to;
                $durations[$key][] = $history[$i]->created_at->diffInMinutes($left);
            }
        }

        $rows = [];
        foreach ($durations as $key => $minutes) {
            [$serviceKey, $stage] = explode('|', $key, 2);
            $workflow = $this->registry->all()[$serviceKey] ?? null;
            $definition = $workflow?->stages()[$stage] ?? null;
            if (($definition['state'] ?? null) !== WorkItem::STATE_OPEN) {
                continue;
            }

            $allowance = $this->deadlines->allowance($serviceKey, $stage);
            $values = collect($minutes);

            $rows[] = [
                'service' => $workflow->label(),
                'stage' => $definition['label'],
                'count' => $values->count(),
                'median_minutes' => $this->median($values),
                'slowest_minutes' => (int) $values->max(),
                'allowance_minutes' => $allowance,
                'over_allowance' => $allowance ? $values->filter(fn (float|int $m) => $m > $allowance)->count() : null,
            ];
        }

        // Slowest first: the steps most worth looking at.
        return collect($rows)->sortByDesc('median_minutes')->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private function departments(CarbonInterface $from, CarbonInterface $to, ?string $service): array
    {
        return Department::query()->orderBy('name')->get()->map(function (Department $department) use ($from, $to, $service): array {
            $items = fn () => $this->items($service)->where('department_id', $department->getKey());
            $received = $this->escalations($service)->where('to_department_id', $department->getKey())
                ->whereBetween('escalations.created_at', [$from, $to])->get(['escalations.*']);

            return [
                'name' => $department->name,
                'open' => $items()->where('state', WorkItem::STATE_OPEN)->count(),
                'unclaimed' => $items()->where('state', WorkItem::STATE_OPEN)->whereNull('owner_id')->count(),
                'overdue' => $items()->active()->where('due_at', '<', now())->count(),
                'completed' => $items()->whereIn('id', $this->completedIds($from, $to, $service))->count(),
                'escalations_received' => $received->count(),
                'response_hours' => $this->median($received
                    ->map(fn (Escalation $e) => ($e->accepted_at ?? $e->closed_at)?->diffInMinutes($e->created_at, absolute: true))
                    ->filter(fn ($minutes) => $minutes !== null)
                    ->map(fn ($minutes) => $minutes / 60)),
            ];
        })->all();
    }

    /** @return list<array<string, mixed>> */
    private function people(CarbonInterface $from, CarbonInterface $to, ?string $service): array
    {
        $staff = User::query()->whereNull('deactivated_at')
            ->where(fn (Builder $query) => $query->whereNotNull('department_id')->orWhere('is_admin', true))
            ->with('department')->orderBy('name')->get();

        $count = fn (Builder $query, string $column) => $query->selectRaw("{$column} as person, count(*) as n")->groupBy($column)->pluck('n', 'person');

        $owned = $count($this->items($service)->active(), 'owner_id');
        $overdue = $count($this->items($service)->active()->where('due_at', '<', now()), 'owner_id');
        $completed = $count($this->items($service)->whereIn('id', $this->completedIds($from, $to, $service)), 'owner_id');
        $claimed = $count($this->events($service)->where('work_item_events.type', WorkItemEvent::CLAIMED)->whereBetween('work_item_events.created_at', [$from, $to]), 'work_item_events.user_id');
        $raised = $count($this->escalations($service)->whereBetween('escalations.created_at', [$from, $to]), 'raised_by');
        $resolved = $count($this->escalations($service)->where('status', Escalation::STATUS_RESOLVED)->whereBetween('escalations.closed_at', [$from, $to]), 'responded_by');
        $actions = ActivityLog::query()->whereBetween('created_at', [$from, $to])
            ->selectRaw('user_id as person, count(*) as n')->groupBy('user_id')->pluck('n', 'person');

        return $staff->map(fn (User $user): array => [
            'name' => $user->name,
            'department' => $user->department?->name ?? ($user->isAdmin() ? 'CEO' : '-'),
            'owned' => (int) ($owned[$user->id] ?? 0),
            'overdue' => (int) ($overdue[$user->id] ?? 0),
            'completed' => (int) ($completed[$user->id] ?? 0),
            'claimed' => (int) ($claimed[$user->id] ?? 0),
            'escalations_raised' => (int) ($raised[$user->id] ?? 0),
            'escalations_resolved' => (int) ($resolved[$user->id] ?? 0),
            'actions' => (int) ($actions[$user->id] ?? 0),
        ])->sortByDesc(fn (array $row) => [$row['completed'], $row['owned']])->values()->all();
    }

    /**
     * Items that moved into a finished step inside the period. Read from the
     * history rather than closed_at: an item created already finished (every
     * old booking the backfill picked up) was not completed by anyone.
     *
     * @return Collection<int, int>
     */
    private function completedIds(CarbonInterface $from, CarbonInterface $to, ?string $service): Collection
    {
        return $this->cache["completed:{$from}:{$to}:{$service}"] ??= $this->events($service)
            ->where('work_item_events.type', WorkItemEvent::STAGE_CHANGED)
            ->whereBetween('work_item_events.created_at', [$from, $to])
            ->where('work_items.state', WorkItem::STATE_DONE)
            ->get(['work_item_events.work_item_id', 'work_item_events.to', 'work_items.service'])
            ->filter(fn ($event) => ($this->registry->all()[$event->service]?->stages()[$event->to]['state'] ?? null) === WorkItem::STATE_DONE)
            ->pluck('work_item_id')
            ->unique()
            ->values();
    }

    /** @var array<string, Collection<int, int>> */
    private array $cache = [];

    private function items(?string $service): Builder
    {
        return WorkItem::query()->when($service, fn (Builder $query) => $query->where('service', $service));
    }

    private function events(?string $service): Builder
    {
        return WorkItemEvent::query()
            ->join('work_items', 'work_items.id', '=', 'work_item_events.work_item_id')
            ->when($service, fn (Builder $query) => $query->where('work_items.service', $service));
    }

    private function escalations(?string $service): Builder
    {
        return Escalation::query()
            ->join('work_items', 'work_items.id', '=', 'escalations.work_item_id')
            ->when($service, fn (Builder $query) => $query->where('work_items.service', $service));
    }

    private function median(Collection $values): ?float
    {
        $sorted = $values->map(fn ($value) => (float) $value)->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        return round($count % 2 ? $sorted[$middle] : ($sorted[$middle - 1] + $sorted[$middle]) / 2, 1);
    }
}
