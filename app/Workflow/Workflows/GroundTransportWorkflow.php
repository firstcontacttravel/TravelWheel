<?php

namespace App\Workflow\Workflows;

use App\Models\WorkItem;
use App\Workflow\ServiceWorkflow;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Car hire and transfers: assign a driver, then the trip happens. Assigning a
 * driver (the existing Assign driver action) moves the booking on by itself.
 */
abstract class GroundTransportWorkflow extends ServiceWorkflow
{
    protected function paymentColumn(): string
    {
        return 'payment_status';
    }

    protected function paidValue(): string
    {
        return 'paid';
    }

    protected function department(): string
    {
        return 'ground-airport';
    }

    public function referenceColumn(): string
    {
        return 'payment_reference';
    }

    protected function steps(): array
    {
        return [
            'new' => ['label' => 'Assign a driver', 'state' => WorkItem::STATE_OPEN, 'next' => ['driver_assigned', 'completed']],
            'driver_assigned' => ['label' => 'Driver assigned, awaiting trip', 'state' => WorkItem::STATE_WAITING, 'next' => ['completed']],
            'completed' => ['label' => 'Trip completed', 'state' => WorkItem::STATE_DONE, 'next' => []],
        ];
    }

    public function stageFor(Model $subject): string
    {
        $stage = parent::stageFor($subject);

        return $stage === 'new' && $subject->getAttribute('driver_assigned') ? 'driver_assigned' : $stage;
    }

    public function serviceDate(Model $subject): ?CarbonInterface
    {
        return $this->parseDate($this->raw($subject, 'pickup_date'), $this->raw($subject, 'pickup_time'));
    }
}
