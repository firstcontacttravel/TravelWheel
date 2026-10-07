<?php

namespace App\Workflow;

use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use App\Services\DurableMailService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Run every five minutes (workflow:check-deadlines). Three alerts per step,
 * each sent once:
 *
 *   warning  most of the time allowed is gone      → owner, or the queue (bell)
 *   missed   the due time has passed                → owner and queue (bell + email)
 *   CEO      still not done a while after missing it → the CEO (bell + email)
 *
 * The owner is who must act. With no owner, everyone in the item's
 * department hears, because nobody has picked it up.
 */
class DeadlineMonitor
{
    public function __construct(
        private readonly Deadlines $deadlines,
        private readonly DurableMailService $mail,
    ) {}

    /** @return array{warned: int, missed: int, ceo: int} */
    public function run(): array
    {
        $settings = $this->deadlines->settings();
        $counts = ['warned' => 0, 'missed' => 0, 'ceo' => 0];

        $this->candidates()
            ->whereNull('warned_at')
            ->whereNull('breached_at')
            ->where('due_at', '>', now())
            ->get()
            ->filter(fn (WorkItem $item) => $this->warningDue($item, $settings['warn_percent']))
            ->each(function (WorkItem $item) use (&$counts): void {
                $this->alert($item, 'warned_at', WorkItemEvent::DEADLINE_WARNING, $this->team($item), 'Due '.$item->due_at->diffForHumans(), email: null);
                $counts['warned']++;
            });

        $this->candidates()
            ->whereNull('breached_at')
            ->where('due_at', '<=', now())
            ->get()
            ->each(function (WorkItem $item) use (&$counts): void {
                $this->alert($item, 'breached_at', WorkItemEvent::DEADLINE_MISSED, $this->team($item, everyone: true), 'Overdue since '.$this->time($item), email: 'missed');
                $counts['missed']++;
            });

        $this->candidates()
            ->whereNull('ceo_alerted_at')
            ->whereNotNull('breached_at')
            ->where('breached_at', '<=', now()->subMinutes($settings['ceo_after_minutes']))
            ->get()
            ->each(function (WorkItem $item) use (&$counts): void {
                $ceo = User::query()->where('is_admin', true)->whereNull('deactivated_at')->get();
                $this->alert($item, 'ceo_alerted_at', WorkItemEvent::DEADLINE_CEO, $ceo, 'Overdue since '.$this->time($item).', still not done', email: 'ceo');
                $counts['ceo']++;
            });

        return $counts;
    }

    private function candidates()
    {
        return WorkItem::query()->active()->whereNotNull('due_at')->with(['subject', 'owner', 'department']);
    }

    private function warningDue(WorkItem $item, int $percent): bool
    {
        $start = $item->stage_entered_at ?? $item->created_at;
        $window = $start->diffInSeconds($item->due_at, absolute: true);

        return now()->greaterThanOrEqualTo($start->copy()->addSeconds((int) ($window * $percent / 100)));
    }

    /**
     * The owner; with no owner, the department. "everyone" adds the
     * department even when someone owns it: a missed deadline is the team's
     * problem, not one person's.
     */
    private function team(WorkItem $item, bool $everyone = false): Collection
    {
        $people = collect();

        if ($item->owner && ! $item->owner->isDeactivated()) {
            $people->push($item->owner);
        }

        if (($everyone || $people->isEmpty()) && $item->department_id) {
            $people = $people->concat(User::query()->where('department_id', $item->department_id)->whereNull('deactivated_at')->get());
        }

        return $people->unique('id')->values();
    }

    private function alert(WorkItem $item, string $flag, string $type, Collection $people, string $detail, ?string $email): void
    {
        // Recorded first: if telling people fails, the next run must not
        // repeat an alert the history already shows.
        // Not "touched": an alert is not work, and My Work sorts by last touched.
        $item->timestamps = false;
        $item->forceFill([$flag => now()])->save();
        $item->timestamps = true;
        $item->events()->create(['type' => $type, 'to' => $item->stageLabel(), 'body' => $detail]);

        if (! $item->subject) {
            return;
        }

        try {
            $workflow = $item->workflow();
            $reference = $workflow->reference($item->subject);

            Notification::make()
                ->title(match ($type) {
                    WorkItemEvent::DEADLINE_WARNING => "Due soon: {$reference}",
                    WorkItemEvent::DEADLINE_MISSED => "Overdue: {$reference}",
                    default => "Still overdue: {$reference}",
                })
                ->body($item->stageLabel().'. '.$detail.'.')
                ->icon('heroicon-o-clock')
                ->{$type === WorkItemEvent::DEADLINE_WARNING ? 'warning' : 'danger'}()
                ->actions([Action::make('open')->label('Open')->url($workflow->url($item->subject))->markAsRead()])
                ->sendToDatabase($people);

            if ($email) {
                foreach ($people as $person) {
                    $this->mail->store(
                        DurableMailService::WORK_DEADLINE,
                        (string) $person->email,
                        $item,
                        ['event' => $email],
                        "deadline:{$item->getKey()}:{$email}:{$item->{$flag}->timestamp}:{$person->getKey()}",
                    );
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function time(WorkItem $item): string
    {
        return $item->due_at->copy()->timezone('Africa/Lagos')->format('d M, H:i');
    }
}
