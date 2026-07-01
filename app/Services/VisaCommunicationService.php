<?php

namespace App\Services;

use App\Mail\VisaApplicationUpdateMail;
use App\Models\VisaAdditionalDocumentRequest;
use App\Models\VisaApplication;
use App\Models\VisaNotificationEvent;
use Illuminate\Support\Facades\Mail;

class VisaCommunicationService
{
    public function documentRequested(VisaAdditionalDocumentRequest $request): ?VisaNotificationEvent
    {
        return $this->sendOnce(
            $request->application,
            'document_request:'.$request->id,
            'Action required for visa application '.$request->application->reference,
            'A document is required',
            $request->title.'. '.($request->instructions ?: 'Please open the secure customer portal to upload the requested document.')
        );
    }

    public function statusChanged(VisaApplication $application, string $status): ?VisaNotificationEvent
    {
        [$subject, $heading, $message] = match ($status) {
            'submitted' => ['Visa application submitted', 'Application submitted', 'We have received your visa application and will begin our checks.'],
            'in_review', 'processing' => ['Visa application update', 'Application in progress', 'Your visa application is currently being reviewed.'],
            'under_review' => ['Visa application update', 'Application in review', 'A visa officer is reviewing your application.'],
            'action_required' => ['Action required for your visa application', 'Action required', 'Please open the secure customer portal and complete the outstanding request.'],
            'approved' => ['Visa application approved', 'Application approved', 'Your visa application has been approved. We will notify you when the issued document is available.'],
            'issued' => ['Your visa document is ready', 'Visa issued', 'Your issued visa document is available for secure download in the customer portal.'],
            'rejected' => ['Visa application decision', 'Application decision available', 'A decision has been recorded for your visa application. Open the portal for the latest status and next steps.'],
            'cancelled' => ['Visa application cancelled', 'Application cancelled', 'Your visa application has been cancelled. Contact support if you need assistance.'],
            default => ['Visa application update', 'Application updated', 'There is a new update on your visa application.'],
        };

        return $this->sendOnce($application, 'status:'.$status, $subject.' — '.$application->reference, $heading, $message);
    }

    public function sendOnce(VisaApplication $application, string $type, string $subject, string $heading, string $message): ?VisaNotificationEvent
    {
        if (! $application->contact_email) {
            return null;
        }

        $event = VisaNotificationEvent::query()->firstOrCreate(
            ['visa_application_id' => $application->id, 'event_type' => $type],
            ['recipient' => $application->contact_email, 'subject' => $subject, 'payload' => compact('heading', 'message'), 'status' => 'queued', 'queued_at' => now()]
        );
        if ($event->wasRecentlyCreated) {
            Mail::to($event->recipient)->queue(new VisaApplicationUpdateMail($application, $heading, $message, $subject));
        }

        return $event;
    }

    public function resend(VisaNotificationEvent $event): void
    {
        $application = VisaApplication::query()->findOrFail($event->visa_application_id);
        Mail::to($event->recipient)->queue(new VisaApplicationUpdateMail($application, data_get($event->payload, 'heading', 'Application update'), data_get($event->payload, 'message', ''), $event->subject));
        $event->update(['status' => 'queued', 'queued_at' => now(), 'resent_at' => now()]);
    }
}
