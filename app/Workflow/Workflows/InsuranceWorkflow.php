<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\InsurancePurchases\InsurancePurchaseResource;
use App\Models\Department;
use App\Models\InsurancePurchase;
use App\Models\WorkItem;
use App\Workflow\ServiceWorkflow;
use Illuminate\Database\Eloquent\Model;

/**
 * Insurance purchases are saved after the customer has paid, with "status"
 * saying whether the insurer then issued the policy: "Successful" means it
 * did, "Failed" means a paying customer has no policy. That second case is
 * the only work here — issue the policy by hand and record it.
 */
class InsuranceWorkflow extends ServiceWorkflow
{
    public function service(): string
    {
        return 'insurance';
    }

    public function label(): string
    {
        return 'Insurance';
    }

    public function subjectClass(): string
    {
        return InsurancePurchase::class;
    }

    protected function resource(): string
    {
        return InsurancePurchaseResource::class;
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
        return Department::CUSTOMER_SUPPORT;
    }

    public function referenceColumn(): string
    {
        return 'ref_id';
    }

    protected function steps(): array
    {
        return [
            'issue_failed' => ['label' => 'Paid, policy not issued', 'state' => WorkItem::STATE_OPEN, 'next' => ['issued']],
            'issued' => ['label' => 'Policy issued', 'state' => WorkItem::STATE_DONE, 'next' => []],
        ];
    }

    /** Both outcomes follow a payment; "Failed" is the insurer failing, not the customer. */
    public function isPaid(Model $subject): bool
    {
        return in_array(strtolower((string) $subject->getAttribute('status')), ['successful', 'failed'], true);
    }

    public function stageFor(Model $subject): string
    {
        $fulfilment = (string) $subject->getAttribute('fulfilment_status');

        return match (true) {
            $fulfilment === 'cancelled' => 'cancelled',
            $fulfilment === 'issued' => 'issued',
            strtolower((string) $subject->getAttribute('status')) === 'successful' => 'issued',
            strtolower((string) $subject->getAttribute('status')) === 'failed' => 'issue_failed',
            default => 'awaiting_payment',
        };
    }
}
