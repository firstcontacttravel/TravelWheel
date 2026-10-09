<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\LoungeBookings\LoungeBookingResource;
use App\Models\Department;
use App\Models\LoungeBooking;
use App\Models\WorkItem;
use App\Workflow\ServiceWorkflow;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Lounge bookings arrive already paid ("Successful"). Staff confirm the place
 * with the lounge (by hand for LoungePair bookings), then the customer flies.
 */
class LoungeWorkflow extends ServiceWorkflow
{
    public function service(): string
    {
        return 'lounge';
    }

    public function label(): string
    {
        return 'Lounge';
    }

    public function subjectClass(): string
    {
        return LoungeBooking::class;
    }

    protected function resource(): string
    {
        return LoungeBookingResource::class;
    }

    protected function paymentColumn(): string
    {
        return 'status';
    }

    protected function paidValue(): string
    {
        return 'Successful';
    }

    protected function department(): string
    {
        return Department::OPERATIONS;
    }

    public function referenceColumn(): string
    {
        return 'ref_id';
    }

    protected function steps(): array
    {
        return [
            'new' => ['label' => 'Confirm with the lounge', 'state' => WorkItem::STATE_OPEN, 'next' => ['confirmed', 'completed']],
            'confirmed' => ['label' => 'Confirmed, awaiting travel', 'state' => WorkItem::STATE_WAITING, 'next' => ['completed']],
            'completed' => ['label' => 'Used', 'state' => WorkItem::STATE_DONE, 'next' => []],
        ];
    }

    public function serviceDate(Model $subject): ?CarbonInterface
    {
        return $this->parseDate($this->raw($subject, 'travel_date'), $this->raw($subject, 'd_time'));
    }
}
