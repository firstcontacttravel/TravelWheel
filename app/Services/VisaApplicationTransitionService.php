<?php

namespace App\Services;

use App\Models\User;
use App\Models\VisaApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisaApplicationTransitionService
{
    private const TRANSITIONS = [
        'submitted' => ['under_review', 'cancelled'],
        'under_review' => ['action_required', 'processing', 'rejected', 'cancelled'],
        'action_required' => ['under_review', 'cancelled'],
        'processing' => ['action_required', 'approved', 'rejected', 'cancelled'],
        'approved' => ['issued', 'processing'],
        'rejected' => ['under_review'],
        'cancelled' => ['under_review'],
    ];

    public function __construct(private readonly VisaCommunicationService $communications) {}

    public function allowedTargets(VisaApplication $application, User $actor): array
    {
        $targets = self::TRANSITIONS[$application->status] ?? [];
        if (! $this->isAdministrator($actor)) {
            $targets = array_values(array_filter($targets, fn (string $target): bool => ! in_array($application->status, ['rejected', 'cancelled'], true) && ! ($application->status === 'approved' && $target === 'processing')));
        }

        return $targets;
    }

    public function transition(VisaApplication $application, string $to, User $actor, array $context = []): VisaApplication
    {
        if (! in_array($to, $this->allowedTargets($application, $actor), true)) {
            throw ValidationException::withMessages(['status' => "The transition from {$application->status} to {$to} is not allowed."]);
        }
        $publicNote = trim((string) ($context['public_note'] ?? ''));
        $internalNote = trim((string) ($context['internal_note'] ?? ''));
        if (in_array($to, ['rejected', 'cancelled'], true) && ($publicNote === '' || $internalNote === '')) {
            throw ValidationException::withMessages(['status' => 'A public reason and an internal reason are required.']);
        }
        if ($to === 'action_required' && ! $application->additionalDocumentRequests()->whereIn('status', ['open', 'replacement_requested'])->exists()) {
            throw ValidationException::withMessages(['status' => 'Create an open applicant document request before moving to action required.']);
        }
        if ($to === 'approved' && empty($context['decision_date'])) {
            throw ValidationException::withMessages(['status' => 'A decision date is required for approval.']);
        }
        if ($to === 'issued' && ! $application->issuedDocuments()->whereNull('superseded_at')->exists() && blank($context['no_document_reason'] ?? null)) {
            throw ValidationException::withMessages(['status' => 'Upload an issued document or provide an authorized no-document reason.']);
        }
        if ($to === 'issued' && (empty($context['valid_from']) || empty($context['valid_until']) || $context['valid_until'] < $context['valid_from'])) {
            throw ValidationException::withMessages(['status' => 'Valid visa issue and expiry dates are required.']);
        }

        $from = $application->status;
        $application = DB::transaction(function () use ($application, $to, $actor, $context, $publicNote, $internalNote, $from): VisaApplication {
            $changes = ['status' => $to];
            if ($to === 'under_review' && ! $application->assigned_to) {
                $changes += ['assigned_to' => $actor->id, 'assigned_at' => now()];
            }
            if ($to === 'approved') {
                $changes += ['decision_date' => $context['decision_date'], 'decision_reference' => $context['decision_reference'] ?? null];
            }
            if ($to === 'issued') {
                $changes += ['issued_at' => now(), 'visa_valid_from' => $context['valid_from'] ?? null, 'visa_valid_until' => $context['valid_until'] ?? null, 'no_document_reason' => $context['no_document_reason'] ?? null];
            }
            $before = $application->only(array_keys($changes));
            $application->update($changes);
            $application->statusHistory()->create(['from_status' => $from, 'to_status' => $to, 'actor_type' => 'user', 'actor_id' => $actor->id, 'reason' => $publicNote ?: null, 'metadata' => ['internal_note' => $internalNote ?: null, 'override' => in_array($from, ['rejected', 'cancelled'], true) || ($from === 'approved' && $to === 'processing')]]);
            if ($internalNote !== '') {
                $application->internalNotes()->create(['user_id' => $actor->id, 'body' => $internalNote]);
            }
            $application->auditEvents()->create(['user_id' => $actor->id, 'event_type' => 'status_transition', 'summary' => "Status changed from {$from} to {$to}", 'before' => $before, 'after' => $application->only(array_keys($changes)), 'metadata' => ['public_note' => $publicNote ?: null]]);

            return $application->fresh();
        });
        $this->communications->statusChanged($application, $to);

        return $application;
    }

    private function isAdministrator(User $user): bool
    {
        return $user->isVisaAdministrator();
    }
}
