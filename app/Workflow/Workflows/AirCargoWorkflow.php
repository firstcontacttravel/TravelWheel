<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\AirCargoBookings\AirCargoBookingResource;
use App\Models\AirCargoModel;
use App\Models\Department;
use App\Models\WorkItem;
use App\Workflow\ServiceWorkflow;

/**
 * Air cargo: the shipment is received from the customer, shipped, and
 * delivered.
 */
class AirCargoWorkflow extends ServiceWorkflow
{
    public function service(): string
    {
        return 'air_cargo';
    }

    public function label(): string
    {
        return 'Air Cargo';
    }

    public function subjectClass(): string
    {
        return AirCargoModel::class;
    }

    protected function resource(): string
    {
        return AirCargoBookingResource::class;
    }

    protected function paymentColumn(): string
    {
        return 'payment_status';
    }

    protected function paidValue(): string
    {
        return 'successful';
    }

    protected function department(): string
    {
        return Department::OPERATIONS;
    }

    public function referenceColumn(): string
    {
        return 'shipping_id';
    }

    protected function steps(): array
    {
        return [
            'new' => ['label' => 'Receive the shipment', 'state' => WorkItem::STATE_OPEN, 'next' => ['received']],
            'received' => ['label' => 'Received, ship it', 'state' => WorkItem::STATE_OPEN, 'next' => ['in_transit']],
            'in_transit' => ['label' => 'In transit', 'state' => WorkItem::STATE_WAITING, 'next' => ['delivered']],
            'delivered' => ['label' => 'Delivered', 'state' => WorkItem::STATE_DONE, 'next' => []],
        ];
    }
}
