<?php

namespace App\Workflow;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * How one kind of booking moves through work.
 *
 * A workflow does not own the booking's status. It READS it: stageFor()
 * works the stage out from the columns the existing actions already write,
 * so nothing that books, pays or tickets has to know workflows exist.
 */
abstract class Workflow
{
    /** Short key stored on work_items.service, e.g. "flights". */
    abstract public function service(): string;

    /** What staff call this service, e.g. "Flights". */
    abstract public function label(): string;

    /** @return class-string<Model> */
    abstract public function subjectClass(): string;

    /**
     * Every stage, in the order work moves through them.
     *
     * state: open (someone should act), waiting (on the customer or a
     * supplier), done, cancelled. department: the slug of the queue an
     * unowned item at this stage sits in.
     *
     * @return array<string, array{label: string, state: string, department: string}>
     */
    abstract public function stages(): array;

    abstract public function stageFor(Model $subject): string;

    /** The booking reference staff search by. */
    abstract public function reference(Model $subject): string;

    /** The column that reference lives in, for searching across services. */
    abstract public function referenceColumn(): string;

    /** The admin page for the booking. */
    abstract public function url(Model $subject): string;

    /** When the work at this stage must be done by, if anything says so. */
    public function dueAt(Model $subject, string $stage): ?CarbonInterface
    {
        return null;
    }

    /**
     * Some bookings already record their owner on themselves (a visa's
     * assigned officer). Returns that user id, null for nobody, or false when
     * the booking has no such column and the work item alone decides.
     */
    public function ownerIdFromSubject(Model $subject): int|false|null
    {
        return false;
    }

    /** Writes an ownership change back to the booking, where it has a column for it. */
    public function applyOwner(Model $subject, ?User $owner, User $actor): void {}

    /** Bookings the backfill should create work items for. */
    public function backfillQuery(): Builder
    {
        return ($this->subjectClass())::query();
    }
}
