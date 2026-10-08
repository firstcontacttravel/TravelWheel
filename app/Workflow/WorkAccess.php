<?php

namespace App\Workflow;

use App\Models\TravelFlexApplication;
use App\Models\User;
use App\Models\WorkItem;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may do what to a booking.
 *
 *   Anyone in staff   claims an unowned booking.
 *   The owner         works it: every action on the booking, notes,
 *                     releasing it.
 *   A department head manages it: escalates, reassigns, takes it over, sets
 *                     priority, and works it too, for bookings in their
 *                     department's queue or owned by someone in it.
 *   The CEO           does anything.
 *
 * Other checks still apply on top: only Finance moves money, only Operations
 * works visas.
 *
 * Registered on every Filament action (AppServiceProvider), so any action on
 * a booking that a person may not take is hidden. A hidden action is also
 * refused if someone calls it anyway; Filament treats it as disabled.
 */
class WorkAccess
{
    /**
     * Actions that carry their own rule (claiming, the Work and Escalation
     * menus) or only read, and so are left alone here.
     */
    private const OWN_RULES = [
        'view', 'open', 'downloadDocument',
        'workClaim', 'workRelease', 'workReassign', 'workEscalate', 'workPriority', 'workNote',
        'workEscalationAccept', 'workEscalationResolve', 'workEscalationDecline', 'workEscalationWithdraw',
    ];

    /** Handing a booking to someone else is managing it, whatever the screen calls it. */
    private const MANAGEMENT = ['assign'];

    public static function canManage(?User $user, ?WorkItem $item): bool
    {
        if (! $user || $user->isDeactivated()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $item || ! $user->isDepartmentHead()) {
            return false;
        }

        return $item->department_id === $user->department_id
            || ($item->owner && $item->owner->department_id === $user->department_id);
    }

    public static function canWork(?User $user, ?WorkItem $item): bool
    {
        if (! $user || $user->isDeactivated()) {
            return false;
        }

        return $user->isAdmin()
            || ($item && $item->owner_id === $user->getKey())
            || self::canManage($user, $item);
    }

    /** Claiming: anyone takes an unowned booking; taking one from its owner is managing it. */
    public static function canClaim(?User $user, ?WorkItem $item): bool
    {
        if (! $user || $user->isDeactivated() || $item?->owner_id === $user->getKey()) {
            return false;
        }

        return ! $item?->owner_id || self::canManage($user, $item);
    }

    /** True when this action is on a booking the signed-in person may not act on. */
    public static function blocks(Action $action): bool
    {
        $user = auth()->user();
        if (! $user instanceof User || $user->isAdmin() || in_array($action->getName(), self::OWN_RULES, true)) {
            return false;
        }

        $record = $action->getRecord();
        if (! $record instanceof Model) {
            return false;
        }

        $subject = $record instanceof TravelFlexApplication ? $record->booking : $record;
        if (! $subject || ! app(WorkflowRegistry::class)->forSubject($subject)) {
            return false;
        }

        $item = self::itemFor($subject);

        return in_array($action->getName(), self::MANAGEMENT, true)
            ? ! self::canManage($user, $item)
            : ! self::canWork($user, $item);
    }

    public static function itemFor(Model $subject): ?WorkItem
    {
        // One lookup per booking per request: a table asks for every action
        // on every row. Held in a request-scoped container binding, never a
        // static, so nothing carries over between requests.
        $items = app(self::CACHE);
        $key = $subject->getMorphClass().':'.$subject->getKey();

        if (! $items->offsetExists($key)) {
            $items[$key] = $subject->relationLoaded('workItem')
                ? $subject->workItem?->loadMissing('owner')
                : $subject->workItem()->with('owner')->first();
        }

        return $items[$key];
    }

    /** After any change of owner or queue, so the page re-renders with the new state. */
    public static function forget(): void
    {
        app(self::CACHE)->exchangeArray([]);
    }

    /** Container key for the per-request cache (bound as scoped in AppServiceProvider). */
    public const CACHE = 'workflow.access-cache';
}
