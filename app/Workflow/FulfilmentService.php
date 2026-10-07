<?php

namespace App\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moves a paid service booking through its fulfilment steps, cancels it, or
 * records a payment Finance confirmed by hand. Replaces the "Change status"
 * dropdown, which let anyone write any value into the payment column.
 *
 * Saving the booking re-syncs its work item (SyncsWorkItems), which writes
 * the stage change into the history with the signed-in person's name; a note
 * given here is added under it.
 */
class FulfilmentService
{
    public function __construct(
        private readonly WorkflowRegistry $registry,
        private readonly WorkItemService $items,
    ) {}

    public function advance(Model $subject, string $step, User $actor, ?string $note = null): void
    {
        $workflow = $this->workflow($subject);
        $allowed = $workflow->nextSteps($subject);

        if (! array_key_exists($step, $allowed)) {
            $from = $workflow->stages()[$workflow->stageFor($subject)]['label'] ?? $workflow->stageFor($subject);
            throw new InvalidArgumentException("This booking cannot move from \"{$from}\" to that step.");
        }

        $this->save($subject, $step, $actor, $note);
    }

    public function cancel(Model $subject, User $actor, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Say why it is being cancelled.');
        }
        if ($subject->getAttribute('fulfilment_status') === 'cancelled') {
            throw new InvalidArgumentException('This booking is already cancelled.');
        }

        $this->save($subject, 'cancelled', $actor, 'Cancelled: '.$reason);
    }

    /** Finance confirmed a payment that did not come through checkout (a transfer, cash). */
    public function markPaid(Model $subject, User $actor, ?string $note = null): void
    {
        if (! $actor->canHandleMoney()) {
            throw new InvalidArgumentException('Only Finance can mark a payment as received.');
        }

        $workflow = $this->workflow($subject);
        if ($workflow->isPaid($subject)) {
            throw new InvalidArgumentException('This booking is already paid.');
        }

        DB::transaction(function () use ($subject, $workflow, $actor, $note): void {
            $workflow->markPaid($subject);
            $this->note($subject, $actor, 'Payment marked as received'.(filled($note) ? ': '.trim($note) : '.'));
        });
    }

    private function save(Model $subject, string $status, User $actor, ?string $note): void
    {
        DB::transaction(function () use ($subject, $status, $actor, $note): void {
            $subject->setAttribute('fulfilment_status', $status);
            $subject->save();

            if (filled($note)) {
                $this->note($subject, $actor, trim($note));
            }
        });
    }

    private function note(Model $subject, User $actor, string $body): void
    {
        $item = $this->items->for($subject) ?? $this->items->sync($subject, $actor);
        $this->items->addNote($item, $actor, $body);
    }

    private function workflow(Model $subject): ServiceWorkflow
    {
        $workflow = $this->registry->forSubject($subject);

        if (! $workflow instanceof ServiceWorkflow) {
            throw new InvalidArgumentException(class_basename($subject).' has no fulfilment steps.');
        }

        return $workflow;
    }
}
