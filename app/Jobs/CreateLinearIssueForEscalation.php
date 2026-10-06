<?php

namespace App\Jobs;

use App\Models\Escalation;
use App\Models\WorkItemEvent;
use App\Services\Linear\LinearClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use Throwable;

/**
 * Opens the Linear issue for an escalation: in the IT team for IT, in the
 * shared Travelwheel team with the department's label for everyone else.
 *
 * Queued, so a slow or unreachable Linear never holds up the person
 * escalating; the escalation itself is already recorded. Retried a few times,
 * and a final failure is written into the booking's history.
 *
 * The issue carries the booking reference, its stage, the staff-written
 * reason and a link back to the admin. Never passenger, passport or payment
 * details: those stay behind the admin login.
 */
class CreateLinearIssueForEscalation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /** Linear priorities: 1 urgent, 2 high, 3 medium, 4 low. */
    private const PRIORITY = ['urgent' => 1, 'high' => 2, 'normal' => 3, 'low' => 4];

    public function __construct(public int $escalationId) {}

    public function handle(LinearClient $linear): void
    {
        $escalation = Escalation::query()->with(['workItem', 'toDepartment', 'toUser', 'raiser'])->find($this->escalationId);

        if (! $escalation || ! $linear->isConfigured() || $escalation->linear_issue_id || ! $escalation->isActive()) {
            return;
        }

        $department = $escalation->toDepartment;
        $teamKey = (string) config('services.linear.teams.'.($department?->linear_team ?? 'travelwheel'));
        $labelIds = filled($department?->linear_label) ? [$linear->labelId($department->linear_label)] : [];
        $assigneeId = $escalation->toUser ? $linear->userIdByEmail((string) $escalation->toUser->email) : null;

        $issue = $linear->createIssue(
            teamKey: $teamKey,
            title: $this->title($escalation),
            description: $this->description($escalation),
            priority: self::PRIORITY[$escalation->priority] ?? 3,
            labelIds: $labelIds,
            assigneeId: $assigneeId,
        );

        $escalation->update([
            'linear_issue_id' => $issue['id'],
            'linear_identifier' => $issue['identifier'],
            'linear_issue_url' => $issue['url'],
        ]);

        $escalation->workItem->events()->create([
            'type' => WorkItemEvent::LINEAR_LINKED,
            'to' => $issue['identifier'],
            'metadata' => ['escalation_id' => $escalation->getKey(), 'url' => $issue['url']],
        ]);
    }

    public function failed(Throwable $e): void
    {
        $escalation = Escalation::query()->with('workItem')->find($this->escalationId);

        $escalation?->workItem?->events()->create([
            'type' => WorkItemEvent::LINEAR_FAILED,
            'body' => Str::limit($e->getMessage(), 500),
            'metadata' => ['escalation_id' => $escalation->getKey()],
        ]);
    }

    private function title(Escalation $escalation): string
    {
        $item = $escalation->workItem;
        $kind = $escalation->mode === Escalation::MODE_HANDOFF ? 'Hand-off' : 'Help';

        return "{$kind} · {$item->workflow()->reference($item->subject)} · ".Str::limit(Str::squish($escalation->reason), 80);
    }

    private function description(Escalation $escalation): string
    {
        $item = $escalation->workItem;
        $workflow = $item->workflow();

        return implode("\n", [
            $escalation->reason,
            '',
            '---',
            '**Booking:** '.$workflow->reference($item->subject).' ('.$workflow->label().')',
            '**Stage:** '.$item->stageLabel(),
            '**Asked by:** '.($escalation->raiser?->name ?? 'Unknown').' for '.$escalation->targetLabel(),
            '**Mode:** '.($escalation->mode === Escalation::MODE_HANDOFF ? 'Hand-off: whoever accepts it in the admin becomes the owner' : 'Help: the owner keeps the booking'),
            '',
            '[Open in TravelWheel admin]('.$workflow->url($item->subject).')',
            '',
            '_Completing or cancelling this issue closes the escalation in the admin. Comments here appear on the booking._',
        ]);
    }
}
