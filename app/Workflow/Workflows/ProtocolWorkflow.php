<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\ProtocolBookings\ProtocolBookingResource;
use App\Models\ProtocolBooking;
use App\Models\WorkItem;
use App\Workflow\ServiceWorkflow;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Airport protocol arrives already paid ("Successful"). Staff arrange the
 * protocol officer, then the service happens on the travel date.
 */
class ProtocolWorkflow extends ServiceWorkflow
{
    public function service(): string
    {
        return 'protocol';
    }

    public function label(): string
    {
        return 'Protocol';
    }

    public function subjectClass(): string
    {
        return ProtocolBooking::class;
    }

    protected function resource(): string
    {
        return ProtocolBookingResource::class;
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
        return 'ground-airport';
    }

    public function referenceColumn(): string
    {
        return 'ref_id';
    }

    protected function steps(): array
    {
        return [
            'new' => ['label' => 'Arrange a protocol officer', 'state' => WorkItem::STATE_OPEN, 'next' => ['confirmed', 'completed']],
            'confirmed' => ['label' => 'Officer arranged, awaiting travel', 'state' => WorkItem::STATE_WAITING, 'next' => ['completed']],
            'completed' => ['label' => 'Service delivered', 'state' => WorkItem::STATE_DONE, 'next' => []],
        ];
    }

    public function serviceDate(Model $subject): ?CarbonInterface
    {
        return $this->parseDate($this->raw($subject, 'travel_date'), $this->raw($subject, 'd_time'));
    }
}
