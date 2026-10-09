<?php

namespace App\Workflow;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only thing that changes a work item. Every change writes a line of
 * history with who made it; a change with no signed-in person is the system.
 */
class WorkItemService
{
    public function __construct(
        private readonly WorkflowRegistry $registry,
        private readonly Deadlines $deadlines,
    ) {}

    public function for(Model $subject): ?WorkItem
    {
        return WorkItem::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->first();
    }

    /**
     * Brings the work item in line with the booking: creates it the first
     * time, moves the stage when the booking's status moved, and follows an
     * owner recorded on the booking itself. Safe to call any number of times.
     */
    public function sync(Model $subject, ?User $actor = null): ?WorkItem
    {
        $workflow = $this->registry->forSubject($subject);
        if (! $workflow) {
            return null;
        }

        $actor ??= auth()->user();
        $stage = $workflow->stageFor($subject);
        $definition = $workflow->stages()[$stage];
        $bookingDeadline = $workflow->dueAt($subject, $stage);
        $subjectOwner = $workflow->ownerIdFromSubject($subject);

        return DB::transaction(function () use ($subject, $workflow, $actor, $stage, $definition, $bookingDeadline, $subjectOwner): WorkItem {
            $item = WorkItem::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey())
                ->lockForUpdate()
                ->first();

            if (! $item) {
                $item = new WorkItem([
                    'service' => $workflow->service(),
                    'stage' => $stage,
                    'state' => $definition['state'],
                    'department_id' => $this->departmentId($definition['department']),
                    'stage_entered_at' => now(),
                    'owner_id' => $subjectOwner ?: null,
                    'claimed_at' => $subjectOwner ? now() : null,
                    'closed_at' => $this->isClosed($definition['state']) ? now() : null,
                ]);
                $item->subject()->associate($subject);
                $item->due_at = $this->deadlines->dueAt($item, $bookingDeadline);
                $item->save();

                $this->event($item, WorkItemEvent::CREATED, null, null, $stage);

                return $item;
            }

            if ($item->stage !== $stage) {
                $from = $item->stage;
                $item->fill([
                    'stage' => $stage,
                    'state' => $definition['state'],
                    'closed_at' => $this->isClosed($definition['state']) ? ($item->closed_at ?? now()) : null,
                    // A new step starts a new clock and a clean set of alerts.
                    'stage_entered_at' => now(),
                    'warned_at' => null,
                    'breached_at' => null,
                    'ceo_alerted_at' => null,
                ]);
                // An unowned item moves to whichever queue the new stage
                // belongs in. An owned one stays with its owner.
                if (! $item->owner_id) {
                    $item->department_id = $this->departmentId($definition['department']);
                }
                $this->event($item, WorkItemEvent::STAGE_CHANGED, $actor, $from, $stage);
            }

            $dueAt = $this->deadlines->dueAt($item, $bookingDeadline);
            if ($item->due_at?->toIso8601String() !== $dueAt?->toIso8601String()) {
                $item->due_at = $dueAt;
                // Pushed later (a new allowance, an extended airline limit):
                // whatever was sent about the old time no longer applies.
                if ($dueAt?->isFuture()) {
                    $item->fill(['warned_at' => null, 'breached_at' => null, 'ceo_alerted_at' => null]);
                }
            }

            if ($subjectOwner !== false && (int) $item->owner_id !== (int) $subjectOwner) {
                $this->changeOwner($item, $subjectOwner ? User::find($subjectOwner) : null, $actor);
            }

            $item->save();
            WorkAccess::forget();

            return $item;
        });
    }

    public function claim(WorkItem $item, User $actor): WorkItem
    {
        if (! WorkAccess::canClaim($actor, $item->loadMissing('owner'))) {
            throw new InvalidArgumentException($item->owner_id
                ? "{$item->owner?->name} owns this. Only a department head can take it over."
                : 'You cannot claim this.');
        }

        return $this->handOver($item, $actor, null, $actor);
    }

    public function release(WorkItem $item, User $actor): WorkItem
    {
        if ($item->owner_id !== $actor->getKey() && ! WorkAccess::canManage($actor, $item->loadMissing('owner'))) {
            throw new InvalidArgumentException('Only the owner or a department head can release this.');
        }

        return $this->handOver($item, null, null, $actor);
    }

    /**
     * Reassigning: hands the item to a person, a department's queue, or both.
     * Naming only a department returns it to that queue for anyone to claim.
     * Department heads and the CEO only.
     */
    public function assign(WorkItem $item, ?User $owner, ?Department $department, User $actor, ?string $note = null): WorkItem
    {
        if (! WorkAccess::canManage($actor, $item->loadMissing('owner'))) {
            throw new InvalidArgumentException('Only a department head can reassign this.');
        }

        return $this->handOver($item, $owner, $department, $actor, $note);
    }

    /**
     * Moves ownership and queue with no permission check. For callers that
     * have already decided the move is allowed: claim and release above, and
     * an accepted hand-off, where the escalation itself was the permission.
     */
    public function handOver(WorkItem $item, ?User $owner, ?Department $department, User $actor, ?string $note = null): WorkItem
    {
        if ($owner?->isDeactivated()) {
            throw new InvalidArgumentException("{$owner->name} is deactivated.");
        }

        DB::transaction(function () use ($item, $owner, $department, $actor, $note): void {
            $item->refresh();

            $this->changeOwner($item, $owner, $actor, $note);

            if ($department && $department->getKey() !== $item->department_id) {
                $from = $item->department?->name;
                $item->department()->associate($department);
                $this->event($item, WorkItemEvent::MOVED, $actor, $from, $department->name, $owner ? null : $note, ['department_id' => $department->getKey()]);
            } elseif (! $department && $owner?->department_id && $owner->department_id !== $item->department_id) {
                // A person carries the item into their own team's queue.
                $item->department_id = $owner->department_id;
            }

            $item->save();

            // The permission check may have loaded the previous owner; the
            // booking must be told about the new one.
            $item->unsetRelation('owner');
            $item->workflow()->applyOwner($item->subject, $item->owner, $actor);
        });

        WorkAccess::forget();

        return $item->fresh();
    }

    public function addNote(WorkItem $item, User $actor, string $body): WorkItemEvent
    {
        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('A note cannot be empty.');
        }
        if (! WorkAccess::canWork($actor, $item->loadMissing('owner'))) {
            throw new InvalidArgumentException('Claim it first: only the owner or a department head can add notes.');
        }

        $item->touch();

        return $this->event($item, WorkItemEvent::NOTE, $actor, null, null, $body);
    }

    public function setPriority(WorkItem $item, string $priority, User $actor): WorkItem
    {
        if (! array_key_exists($priority, WorkItem::PRIORITIES)) {
            throw new InvalidArgumentException("Unknown priority [{$priority}].");
        }
        if (! WorkAccess::canManage($actor, $item->loadMissing('owner'))) {
            throw new InvalidArgumentException('Only a department head can set priority.');
        }

        if ($item->priority !== $priority) {
            $from = $item->priority;
            $item->update(['priority' => $priority]);
            $this->event($item, WorkItemEvent::PRIORITY_CHANGED, $actor, $from, $priority);
        }

        return $item;
    }

    private function changeOwner(WorkItem $item, ?User $owner, ?User $actor, ?string $note = null): void
    {
        $fromId = $item->owner_id;
        $toId = $owner?->getKey();

        if ((int) $fromId === (int) $toId) {
            return;
        }

        $from = $fromId ? User::find($fromId)?->name : null;
        $item->owner_id = $toId;
        $item->claimed_at = $toId ? now() : null;

        $type = match (true) {
            $toId === null => WorkItemEvent::RELEASED,
            $actor !== null && $toId === $actor->getKey() => WorkItemEvent::CLAIMED,
            default => WorkItemEvent::REASSIGNED,
        };

        $this->event($item, $type, $actor, $from, $owner?->name, $note, ['owner_id' => $toId]);
    }

    private function event(WorkItem $item, string $type, ?User $actor, ?string $from, ?string $to, ?string $body = null, ?array $metadata = null): WorkItemEvent
    {
        if (! $item->exists) {
            $item->save();
        }

        return $item->events()->create([
            'type' => $type,
            'user_id' => $actor?->getKey(),
            'from' => $from,
            'to' => $to,
            'body' => $body,
            'metadata' => $metadata,
        ]);
    }

    /** @var array<string, int|null> */
    private array $departmentIds = [];

    private function departmentId(string $slug): ?int
    {
        return $this->departmentIds[$slug] ??= Department::query()->where('slug', $slug)->value('id');
    }

    private function isClosed(string $state): bool
    {
        return in_array($state, [WorkItem::STATE_DONE, WorkItem::STATE_CANCELLED], true);
    }
}
