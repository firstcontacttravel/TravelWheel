<?php

namespace App\Services;

use App\Models\User;
use App\Models\VisaAdditionalDocumentRequest;
use App\Models\VisaApplication;
use App\Models\VisaApplicationDocument;
use App\Models\VisaIssuedDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VisaOperationsService
{
    public function __construct(private readonly VisaCommunicationService $communications, private readonly VisaApplicationTransitionService $transitions) {}

    public function assign(VisaApplication $application, ?User $assignee, User $actor): void
    {
        $before = ['assigned_to' => $application->assigned_to];
        $application->update(['assigned_to' => $assignee?->id, 'assigned_at' => $assignee ? now() : null]);
        $application->auditEvents()->create(['user_id' => $actor->id, 'event_type' => 'assignment', 'summary' => $assignee ? 'Assigned to '.$assignee->name : 'Returned to shared queue', 'before' => $before, 'after' => ['assigned_to' => $assignee?->id]]);
    }

    public function addNote(VisaApplication $application, User $actor, string $body): void
    {
        $application->internalNotes()->create(['user_id' => $actor->id, 'body' => $body]);
        $application->auditEvents()->create(['user_id' => $actor->id, 'event_type' => 'internal_note', 'summary' => 'Internal note added']);
    }

    public function requestDocument(VisaApplication $application, User $actor, array $data): VisaAdditionalDocumentRequest
    {
        $request = $application->additionalDocumentRequests()->create($data + ['requested_by' => $actor->id, 'status' => 'open']);
        $application->auditEvents()->create(['user_id' => $actor->id, 'event_type' => 'document_requested', 'summary' => 'Requested document: '.$request->title, 'after' => $request->only(['id', 'title', 'due_at', 'visa_traveler_id'])]);
        $this->communications->documentRequested($request->load('application'));
        if (in_array($application->status, ['under_review', 'processing'], true)) {
            $this->transitions->transition($application->fresh(), 'action_required', $actor, ['public_note' => $request->instructions ?: $request->title, 'internal_note' => 'Applicant document requested.']);
        }

        return $request;
    }

    public function reviewApplicationDocument(VisaApplicationDocument $document, User $actor, string $status, ?string $note): void
    {
        $before = $document->only(['status', 'review_note']);
        $document->update(['status' => $status, 'review_note' => $note, 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);
        $document->application->auditEvents()->create(['user_id' => $actor->id, 'event_type' => 'document_reviewed', 'summary' => "Document {$status}: {$document->original_name}", 'before' => $before, 'after' => $document->only(['status', 'review_note'])]);
    }

    public function reviewRequestedDocument(VisaAdditionalDocumentRequest $request, User $actor, string $status, ?string $note): void
    {
        $request->update(['status' => $status, 'reviewed_by' => $actor->id, 'review_note' => $note, 'resolved_at' => $status === 'accepted' ? now() : null]);
        $request->application->auditEvents()->create(['user_id' => $actor->id, 'event_type' => 'requested_document_reviewed', 'summary' => "Requested document {$status}: {$request->title}", 'after' => ['status' => $status, 'note' => $note]]);
        if ($status === 'replacement_requested') {
            $this->communications->sendOnce($request->application, 'document_replacement:'.$request->id, 'Replacement document required — '.$request->application->reference, 'Please replace a document', $note ?: 'The previously uploaded document needs to be replaced. Open the portal for details.');
        }
    }

    public function issue(VisaApplication $application, User $actor, array $data): VisaIssuedDocument
    {
        return DB::transaction(function () use ($application, $actor, $data): VisaIssuedDocument {
            $current = $application->issuedDocuments()->whereNull('superseded_at')->first();
            $current?->update(['superseded_at' => now()]);
            $path = $data['document_path'];
            $document = $application->issuedDocuments()->create(['version' => ((int) $application->issuedDocuments()->max('version')) + 1, 'disk' => 'local', 'path' => $path, 'original_name' => $data['document_name'] ?? basename($path), 'mime_type' => Storage::disk('local')->mimeType($path) ?: 'application/pdf', 'size' => Storage::disk('local')->size($path), 'issued_by' => $actor->id, 'issued_at' => now()]);
            $application->auditEvents()->create(['user_id' => $actor->id, 'event_type' => 'visa_document_issued', 'summary' => 'Issued visa document version '.$document->version, 'after' => ['document_id' => $document->id, 'version' => $document->version]]);

            return $document;
        });
    }
}
