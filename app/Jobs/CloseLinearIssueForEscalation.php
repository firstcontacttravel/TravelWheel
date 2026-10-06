<?php

namespace App\Jobs;

use App\Models\Escalation;
use App\Services\Linear\LinearClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * When an escalation closes in the admin: moves its Linear issue to Done
 * (resolved) or Canceled (declined, withdrawn), comments with the outcome,
 * then archives it so it stops counting against the free plan's issue cap.
 *
 * When the escalation was closed FROM Linear the issue is already in its
 * final state with its own comments; only the archive is left to do.
 */
class CloseLinearIssueForEscalation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /** Ends every comment the admin posts, so the webhook can ignore our own echo. */
    public const SIGNATURE = '— TravelWheel admin';

    public function __construct(public int $escalationId, public bool $closedInLinear = false) {}

    public function handle(LinearClient $linear): void
    {
        $escalation = Escalation::query()->with(['toDepartment', 'responder'])->find($this->escalationId);

        if (! $escalation || ! $linear->isConfigured() || ! $escalation->linear_issue_id || $escalation->isActive()) {
            return;
        }

        if (! $this->closedInLinear) {
            $teamKey = (string) config('services.linear.teams.'.($escalation->toDepartment?->linear_team ?? 'travelwheel'));

            $linear->moveIssueTo(
                $escalation->linear_issue_id,
                $teamKey,
                $escalation->status === Escalation::STATUS_RESOLVED ? 'completed' : 'canceled',
            );
            $linear->comment($escalation->linear_issue_id, $this->comment($escalation));
        }

        $linear->archive($escalation->linear_issue_id);
    }

    private function comment(Escalation $escalation): string
    {
        $who = $escalation->responder?->name;

        $line = match ($escalation->status) {
            Escalation::STATUS_RESOLVED => $escalation->mode === Escalation::MODE_HANDOFF
                ? "Taken over in the admin by {$who}."
                : "Resolved in the admin by {$who}.",
            Escalation::STATUS_DECLINED => "Declined in the admin by {$who}.",
            default => 'Withdrawn in the admin; no longer needed.',
        };

        return trim($line."\n\n".($escalation->response_note ?? ''))."\n\n_".self::SIGNATURE.'_';
    }
}
