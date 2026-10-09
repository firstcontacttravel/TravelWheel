<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\WorkItem;
use App\Workflow\Workflow;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Flight bookings. Stages are read from payment_status, booking_status,
 * ticket_ordered and open post-ticketing requests — the same rules as the
 * queue tabs on the Flight Bookings list, so the two never disagree.
 */
class FlightWorkflow extends Workflow
{
    /** Post-ticketing requests still with the supplier. */
    public const ACTIVE_PTR_STATUSES = ['pending', 'submitted', 'in_process', 'inprocess'];

    /**
     * Requests that actually change the ticket. Quotes are left out: they are
     * information, and an unanswered quote stays "inprocess" at the supplier
     * forever, which would hold a finished booking open for good.
     */
    public const CHANGING_PTR_OPERATIONS = ['void', 'refund', 'reissue', 'cancel'];

    /** Stages where the airline's ticketing deadline is the due time. */
    private const DEADLINE_STAGES = ['awaiting_transfer', 'travelflex_review', 'ready_to_ticket', 'ticketing_failed', 'hold_expired'];

    public function service(): string
    {
        return 'flights';
    }

    public function label(): string
    {
        return 'Flights';
    }

    public function subjectClass(): string
    {
        return FlightBooking::class;
    }

    public function stages(): array
    {
        return [
            'pending_payment' => ['label' => 'Awaiting payment', 'state' => WorkItem::STATE_WAITING, 'department' => Department::OPERATIONS],
            'awaiting_transfer' => ['label' => 'Confirm bank transfer', 'state' => WorkItem::STATE_OPEN, 'department' => Department::FINANCE],
            'travelflex_review' => ['label' => 'TravelFlex review', 'state' => WorkItem::STATE_OPEN, 'department' => Department::FINANCE],
            'awaiting_deposit' => ['label' => 'Awaiting TravelFlex deposit', 'state' => WorkItem::STATE_WAITING, 'department' => Department::OPERATIONS],
            'hold_expired' => ['label' => 'Hold expired, rebook', 'state' => WorkItem::STATE_OPEN, 'department' => Department::OPERATIONS],
            'ready_to_ticket' => ['label' => 'Ready to ticket', 'state' => WorkItem::STATE_OPEN, 'department' => Department::OPERATIONS],
            'ticketing_in_progress' => ['label' => 'Ticketing in progress', 'state' => WorkItem::STATE_WAITING, 'department' => Department::OPERATIONS],
            'ticketing_failed' => ['label' => 'Ticketing failed', 'state' => WorkItem::STATE_OPEN, 'department' => Department::OPERATIONS],
            'post_ticketing' => ['label' => 'Change with supplier', 'state' => WorkItem::STATE_WAITING, 'department' => Department::OPERATIONS],
            'ticketed' => ['label' => 'Ticketed', 'state' => WorkItem::STATE_DONE, 'department' => Department::OPERATIONS],
            'cancelled' => ['label' => 'Cancelled', 'state' => WorkItem::STATE_CANCELLED, 'department' => Department::OPERATIONS],
        ];
    }

    public function stageFor(Model $subject): string
    {
        /** @var FlightBooking $subject */
        $booking = $subject->booking_status;
        $payment = $subject->payment_status;

        if ($booking === 'cancelled') {
            return 'cancelled';
        }

        if ($booking === 'ticketed') {
            return $subject->postTicketingRequests()
                ->whereIn('operation_type', self::CHANGING_PTR_OPERATIONS)
                ->whereIn('status', self::ACTIVE_PTR_STATUSES)
                ->exists()
                ? 'post_ticketing'
                : 'ticketed';
        }

        if ($payment === 'awaiting_bank_transfer') {
            return 'awaiting_transfer';
        }

        if (in_array($booking, ['hold_expired_review', 'awaiting_rebooking'], true)) {
            return 'hold_expired';
        }

        if ($booking === 'awaiting_approval') {
            return 'travelflex_review';
        }

        if ($booking === 'awaiting_deposit') {
            return 'awaiting_deposit';
        }

        if ($payment === 'paid') {
            if (in_array($booking, ['failed', 'ticketing_failed'], true)) {
                return 'ticketing_failed';
            }

            return $subject->ticket_ordered ? 'ticketing_in_progress' : 'ready_to_ticket';
        }

        return 'pending_payment';
    }

    public function dueAt(Model $subject, string $stage): ?CarbonInterface
    {
        /** @var FlightBooking $subject */
        return in_array($stage, self::DEADLINE_STAGES, true) ? $subject->tkt_time_limit : null;
    }

    public function reference(Model $subject): string
    {
        /** @var FlightBooking $subject */
        return (string) ($subject->booking_ref ?: 'Flight #'.$subject->getKey());
    }

    public function referenceColumn(): string
    {
        return 'booking_ref';
    }

    public function url(Model $subject): string
    {
        return FlightBookingResource::getUrl('view', ['record' => $subject]);
    }
}
