<?php

namespace App\Workflow\Workflows;

use App\Models\Department;
use App\Models\WorkItem;
use App\Workflow\ServiceWorkflow;

/**
 * The paid support requests: yellow card, extra luggage, flight assist, visa
 * confirmation. Customer Support picks each one up and completes it.
 */
abstract class SupportRequestWorkflow extends ServiceWorkflow
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
        return Department::CUSTOMER_SUPPORT;
    }

    public function referenceColumn(): string
    {
        return 'payment_reference';
    }

    protected function steps(): array
    {
        return [
            'new' => ['label' => 'Handle the request', 'state' => WorkItem::STATE_OPEN, 'next' => ['in_progress', 'completed']],
            'in_progress' => ['label' => 'In progress', 'state' => WorkItem::STATE_OPEN, 'next' => ['completed']],
            'completed' => ['label' => 'Completed', 'state' => WorkItem::STATE_DONE, 'next' => []],
        ];
    }
}
