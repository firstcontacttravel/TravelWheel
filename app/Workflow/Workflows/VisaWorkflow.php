<?php

namespace App\Workflow\Workflows;

use App\Filament\Resources\VisaApplications\VisaApplicationResource;
use App\Models\Department;
use App\Models\User;
use App\Models\VisaApplication;
use App\Models\WorkItem;
use App\Services\VisaOperationsService;
use App\Workflow\Workflow;
use Illuminate\Database\Eloquent\Model;

/**
 * Visa applications. The stage is the application's status, which
 * VisaApplicationTransitionService already guards; the owner is its assigned
 * officer, kept in step both ways so the visa queue and the work item never
 * name different people.
 */
class VisaWorkflow extends Workflow
{
    public function service(): string
    {
        return 'visas';
    }

    public function label(): string
    {
        return 'Visas';
    }

    public function subjectClass(): string
    {
        return VisaApplication::class;
    }

    public function stages(): array
    {
        $visas = Department::OPERATIONS;

        return [
            'draft' => ['label' => 'Customer filling in', 'state' => WorkItem::STATE_WAITING, 'department' => $visas],
            'awaiting_payment' => ['label' => 'Awaiting payment', 'state' => WorkItem::STATE_WAITING, 'department' => $visas],
            'submitted' => ['label' => 'Submitted, not reviewed', 'state' => WorkItem::STATE_OPEN, 'department' => $visas],
            'under_review' => ['label' => 'Under review', 'state' => WorkItem::STATE_OPEN, 'department' => $visas],
            'action_required' => ['label' => 'Waiting on applicant', 'state' => WorkItem::STATE_WAITING, 'department' => $visas],
            'processing' => ['label' => 'Processing', 'state' => WorkItem::STATE_OPEN, 'department' => $visas],
            'approved' => ['label' => 'Approved, issue visa', 'state' => WorkItem::STATE_OPEN, 'department' => $visas],
            'issued' => ['label' => 'Issued', 'state' => WorkItem::STATE_DONE, 'department' => $visas],
            'rejected' => ['label' => 'Rejected', 'state' => WorkItem::STATE_DONE, 'department' => $visas],
            'cancelled' => ['label' => 'Cancelled', 'state' => WorkItem::STATE_CANCELLED, 'department' => $visas],
            'expired' => ['label' => 'Expired before submission', 'state' => WorkItem::STATE_CANCELLED, 'department' => $visas],
        ];
    }

    public function stageFor(Model $subject): string
    {
        /** @var VisaApplication $subject */
        $status = (string) $subject->status;

        return array_key_exists($status, $this->stages()) ? $status : 'draft';
    }

    public function reference(Model $subject): string
    {
        /** @var VisaApplication $subject */
        return (string) ($subject->reference ?: 'Visa #'.$subject->getKey());
    }

    public function referenceColumn(): string
    {
        return 'reference';
    }

    public function url(Model $subject): string
    {
        return VisaApplicationResource::getUrl('view', ['record' => $subject]);
    }

    public function ownerIdFromSubject(Model $subject): int|false|null
    {
        /** @var VisaApplication $subject */
        return $subject->assigned_to ? (int) $subject->assigned_to : null;
    }

    /**
     * Through the visa service, so the visa's own audit trail records the
     * assignment the same way its Assign action does.
     */
    public function applyOwner(Model $subject, ?User $owner, User $actor): void
    {
        /** @var VisaApplication $subject */
        if ((int) $subject->assigned_to === (int) $owner?->getKey()) {
            return;
        }

        app(VisaOperationsService::class)->assign($subject, $owner, $actor);
    }
}
