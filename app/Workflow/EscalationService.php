<?php

namespace App\Workflow;

use App\Jobs\CloseLinearIssueForEscalation;
use App\Jobs\CreateLinearIssueForEscalation;
use App\Models\Department;
use App\Models\Escalation;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use App\Services\DurableMailService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Raising and answering escalations. Every step writes a line in the work
 * item's history and tells the people on the other side, in the admin's
 * notification bell and, for the steps that need someone to act, by email.
 *
 *   help:    open → accepted (optional) → resolved, or declined
 *   handoff: open → resolved on accept (ownership moves), or declined
 *   either:  withdrawn by whoever raised it, while still active
 */
class EscalationService
{
    public function __construct(
        private readonly WorkItemService $items,
        private readonly DurableMailService $mail,
    ) {}

    public function raise(
        WorkItem $item,
        User $raiser,
        string $mode,
        ?Department $department,
        ?User $person,
        string $reason,
        string $priority = 'normal',
        bool $toLinear = false,
    ): Escalation {
        if (! array_key_exists($mode, Escalation::MODES)) {
            throw new InvalidArgumentException("Unknown escalation mode [{$mode}].");
        }
        if (! array_key_exists($priority, WorkItem::PRIORITIES)) {
            throw new InvalidArgumentException("Unknown priority [{$priority}].");
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Say why you are escalating.');
        }
        if (! $department && ! $person) {
            throw new InvalidArgumentException('Escalate to a department, a person, or both.');
        }
        if ($person?->isDeactivated()) {
            throw new InvalidArgumentException("{$person->name} is deactivated.");
        }
        if ($person && $person->is($raiser)) {
            throw new InvalidArgumentException('You cannot escalate to yourself.');
        }

        $department ??= $person?->department;
        // IT works in Linear, so anything sent to IT always goes there.
        $toLinear = $toLinear || $department?->linear_team === 'it';

        $escalation = DB::transaction(function () use ($item, $raiser, $mode, $department, $person, $reason, $priority, $toLinear): Escalation {
            $escalation = $item->escalations()->create([
                'mode' => $mode,
                'status' => Escalation::STATUS_OPEN,
                'raised_by' => $raiser->getKey(),
                'to_department_id' => $department?->getKey(),
                'to_user_id' => $person?->getKey(),
                'priority' => $priority,
                'reason' => trim($reason),
                'linear_requested' => $toLinear,
            ]);

            $this->event($escalation, WorkItemEvent::ESCALATED, $raiser, null, $escalation->targetLabel(), $escalation->reason);

            return $escalation;
        });

        $reference = $this->reference($escalation);
        $this->notify(
            $this->recipients($escalation)->reject(fn (User $user) => $user->is($raiser)),
            ($mode === Escalation::MODE_HANDOFF ? 'Hand-off' : 'Help needed').": {$reference}",
            "{$raiser->name}: {$escalation->reason}",
            $escalation,
            'raised',
        );

        if ($escalation->linear_requested) {
            $this->toLinear(new CreateLinearIssueForEscalation($escalation->getKey()));
        }

        return $escalation;
    }

    public function accept(Escalation $escalation, User $responder): Escalation
    {
        $this->assertCanRespond($escalation, $responder, [Escalation::STATUS_OPEN]);

        DB::transaction(function () use ($escalation, $responder): void {
            $handoff = $escalation->mode === Escalation::MODE_HANDOFF;

            $escalation->update([
                'status' => $handoff ? Escalation::STATUS_RESOLVED : Escalation::STATUS_ACCEPTED,
                'responded_by' => $responder->getKey(),
                'accepted_at' => now(),
                'closed_at' => $handoff ? now() : null,
            ]);

            $this->event($escalation, WorkItemEvent::ESCALATION_ACCEPTED, $responder, null, null);

            if ($handoff) {
                $this->items->assign(
                    $escalation->workItem,
                    $responder,
                    $escalation->toDepartment ?? $responder->department,
                    $responder,
                );
            }
        });

        $this->notify(
            $this->raiser($escalation),
            $escalation->mode === Escalation::MODE_HANDOFF
                ? "{$responder->name} took over {$this->reference($escalation)}"
                : "{$responder->name} is helping with {$this->reference($escalation)}",
            null,
            $escalation,
        );

        $this->closeInLinear($escalation->fresh());

        return $escalation->fresh();
    }

    /** Help only: the problem is sorted; the owner carries on. */
    public function resolve(Escalation $escalation, User $responder, string $note): Escalation
    {
        if ($escalation->mode !== Escalation::MODE_HELP) {
            throw new InvalidArgumentException('A hand-off is resolved by accepting it.');
        }
        $this->assertCanRespond($escalation, $responder, [Escalation::STATUS_OPEN, Escalation::STATUS_ACCEPTED]);
        $note = $this->requireNote($note, 'Say what was done.');

        DB::transaction(function () use ($escalation, $responder, $note): void {
            $escalation->update([
                'status' => Escalation::STATUS_RESOLVED,
                'responded_by' => $responder->getKey(),
                'response_note' => $note,
                'accepted_at' => $escalation->accepted_at ?? now(),
                'closed_at' => now(),
            ]);

            $this->event($escalation, WorkItemEvent::ESCALATION_RESOLVED, $responder, null, null, $note);
        });

        $owner = $escalation->workItem->owner;
        $this->notify(
            $this->raiser($escalation)->push($owner)->filter()->unique('id')->reject(fn (User $user) => $user->is($responder)),
            "Resolved: {$this->reference($escalation)}",
            "{$responder->name}: {$note}",
            $escalation,
            'resolved',
        );

        $this->closeInLinear($escalation->fresh());

        return $escalation->fresh();
    }

    public function decline(Escalation $escalation, User $responder, string $note): Escalation
    {
        $this->assertCanRespond($escalation, $responder, [Escalation::STATUS_OPEN, Escalation::STATUS_ACCEPTED]);
        $note = $this->requireNote($note, 'Say why you are declining.');

        DB::transaction(function () use ($escalation, $responder, $note): void {
            $escalation->update([
                'status' => Escalation::STATUS_DECLINED,
                'responded_by' => $responder->getKey(),
                'response_note' => $note,
                'closed_at' => now(),
            ]);

            $this->event($escalation, WorkItemEvent::ESCALATION_DECLINED, $responder, null, null, $note);
        });

        $this->notify(
            $this->raiser($escalation),
            "Declined: {$this->reference($escalation)}",
            "{$responder->name}: {$note}",
            $escalation,
            'declined',
        );

        $this->closeInLinear($escalation->fresh());

        return $escalation->fresh();
    }

    public function withdraw(Escalation $escalation, User $actor): Escalation
    {
        if (! $escalation->isActive()) {
            throw new InvalidArgumentException('This escalation is already closed.');
        }
        if ($escalation->raised_by !== $actor->getKey() && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('Only whoever raised it can withdraw it.');
        }

        DB::transaction(function () use ($escalation, $actor): void {
            $escalation->update(['status' => Escalation::STATUS_WITHDRAWN, 'closed_at' => now()]);
            $this->event($escalation, WorkItemEvent::ESCALATION_WITHDRAWN, $actor, null, null);
        });

        $this->notify(
            $this->recipients($escalation)->reject(fn (User $user) => $user->is($actor)),
            "Withdrawn: {$this->reference($escalation)}",
            "{$actor->name} no longer needs this.",
            $escalation,
        );

        $this->closeInLinear($escalation->fresh());

        return $escalation->fresh();
    }

    /**
     * Its Linear issue was completed or cancelled. Completing a hand-off
     * means someone took it on, so whoever did it in Linear becomes the owner
     * if they are staff here; otherwise it is resolved and the owner stays.
     */
    public function closeFromLinear(Escalation $escalation, string $stateType, ?User $by, string $byName): void
    {
        if (! $escalation->isActive()) {
            return;
        }

        if ($stateType === 'completed'
            && $escalation->mode === Escalation::MODE_HANDOFF
            && $escalation->status === Escalation::STATUS_OPEN
            && $by && ! $by->isDeactivated() && $escalation->canBeRespondedToBy($by)) {
            $this->accept($escalation, $by);

            return;
        }

        $resolved = $stateType === 'completed';
        $note = ($resolved ? 'Completed' : 'Cancelled')." in Linear by {$byName}.";

        DB::transaction(function () use ($escalation, $by, $resolved, $note): void {
            $escalation->update([
                'status' => $resolved ? Escalation::STATUS_RESOLVED : Escalation::STATUS_DECLINED,
                'responded_by' => $by?->getKey(),
                'response_note' => $note,
                'closed_at' => now(),
            ]);

            $escalation->workItem->events()->create([
                'type' => $resolved ? WorkItemEvent::ESCALATION_RESOLVED : WorkItemEvent::ESCALATION_DECLINED,
                'user_id' => $by?->getKey(),
                'body' => $note,
                'metadata' => ['escalation_id' => $escalation->getKey(), 'mode' => $escalation->mode, 'source' => 'linear'],
            ]);
            $escalation->workItem->touch();
        });

        $this->notify(
            $this->raiser($escalation)->push($escalation->workItem->owner)->filter()->unique('id'),
            ($resolved ? 'Resolved: ' : 'Declined: ').$this->reference($escalation),
            $note,
            $escalation,
            $resolved ? 'resolved' : 'declined',
        );

        $this->toLinear(new CloseLinearIssueForEscalation($escalation->getKey(), closedInLinear: true));
    }

    /** A comment on the escalation's Linear issue, copied into the booking's history. */
    public function commentFromLinear(Escalation $escalation, ?User $by, string $byName, string $body): void
    {
        $body = trim($body);
        if ($body === '') {
            return;
        }

        $escalation->workItem->events()->create([
            'type' => WorkItemEvent::LINEAR_COMMENT,
            'user_id' => $by?->getKey(),
            'from' => $byName,
            'to' => $escalation->linear_identifier,
            'body' => $body,
            'metadata' => ['escalation_id' => $escalation->getKey()],
        ]);
        $escalation->workItem->touch();
    }

    /**
     * Queues the Linear job after the escalation is saved. On a sync queue
     * the job runs right here, so its failure is caught: Linear being down
     * must never undo or block the escalation. The job's own failed() hook
     * has already written the failure into the booking's history.
     */
    private function toLinear(object $job): void
    {
        try {
            // Dispatched here and now, not through dispatch()'s pending object,
            // whose destructor would run outside this try.
            $job->afterCommit();
            Bus::dispatch($job);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function closeInLinear(Escalation $escalation): void
    {
        if ($escalation->linear_issue_id && ! $escalation->isActive()) {
            $this->toLinear(new CloseLinearIssueForEscalation($escalation->getKey()));
        }
    }

    /** The people an escalation is waiting on. */
    public function recipients(Escalation $escalation): Collection
    {
        if ($escalation->to_user_id) {
            return collect([$escalation->toUser])->filter(fn (?User $user) => $user && ! $user->isDeactivated())->values();
        }

        return User::query()
            ->where('department_id', $escalation->to_department_id)
            ->whereNull('deactivated_at')
            ->get();
    }

    private function assertCanRespond(Escalation $escalation, User $user, array $statuses): void
    {
        if (! in_array($escalation->status, $statuses, true)) {
            throw new InvalidArgumentException('This escalation is '.strtolower(Escalation::STATUSES[$escalation->status] ?? $escalation->status).'.');
        }
        if (! $escalation->canBeRespondedToBy($user)) {
            throw new InvalidArgumentException('This escalation is for '.$escalation->targetLabel().'.');
        }
    }

    private function requireNote(string $note, string $message): string
    {
        $note = trim($note);
        if ($note === '') {
            throw new InvalidArgumentException($message);
        }

        return $note;
    }

    private function event(Escalation $escalation, string $type, User $actor, ?string $from, ?string $to, ?string $body = null): void
    {
        $escalation->workItem->events()->create([
            'type' => $type,
            'user_id' => $actor->getKey(),
            'from' => $from,
            'to' => $to,
            'body' => $body,
            'metadata' => ['escalation_id' => $escalation->getKey(), 'mode' => $escalation->mode],
        ]);
        $escalation->workItem->touch();
    }

    private function raiser(Escalation $escalation): Collection
    {
        return collect([$escalation->raiser])->filter()->values();
    }

    private function reference(Escalation $escalation): string
    {
        $item = $escalation->workItem;

        return $item->workflow()->reference($item->subject);
    }

    /**
     * The bell for everyone; an email too for the steps someone has to act
     * on or needs to know the outcome of. Mail goes through the outbox, so a
     * slow mail server never holds up the person escalating.
     */
    private function notify(Collection $users, string $title, ?string $body, Escalation $escalation, ?string $emailEvent = null): void
    {
        $users = $users->filter()->unique('id')->values();
        if ($users->isEmpty()) {
            return;
        }

        try {
            $url = $escalation->workItem->workflow()->url($escalation->workItem->subject);

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-arrow-up-right')
                ->actions([Action::make('open')->label('Open')->url($url)->markAsRead()])
                ->sendToDatabase($users);

            if ($emailEvent) {
                foreach ($users as $user) {
                    $this->mail->store(
                        DurableMailService::WORK_ESCALATION,
                        (string) $user->email,
                        $escalation,
                        ['event' => $emailEvent],
                        "escalation:{$escalation->getKey()}:{$emailEvent}:{$user->getKey()}",
                    );
                }
            }
        } catch (Throwable $e) {
            // The escalation is recorded either way; a lost alert must not
            // undo it.
            report($e);
        }
    }
}
