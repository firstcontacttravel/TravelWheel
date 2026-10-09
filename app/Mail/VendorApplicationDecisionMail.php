<?php

namespace App\Mail;

use App\Models\VendorApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells a vendor they were approved, rejected, or that we need more from them. */
class VendorApplicationDecisionMail extends Mailable
{
    public function __construct(public VendorApplication $application, public string $decision) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->decision) {
            'approved' => 'Your TravelWheel partner application has been approved',
            'rejected' => 'Update on your TravelWheel partner application',
            default => 'We need more information for your partner application',
        };

        return new Envelope(subject: "$subject ({$this->application->reference})", replyTo: [config('vendor_onboarding.notify_email')]);
    }

    public function content(): Content
    {
        $application = $this->application;
        $note = trim((string) $application->decision_note);
        $greeting = "Dear {$application->contact_name},";

        [$heading, $paragraphs, $rows] = match ($this->decision) {
            'approved' => [
                'Application approved',
                [
                    $greeting,
                    'We are pleased to let you know that your application to partner with TravelWheel has been approved. Your Vendor ID and approved services are below. Please quote your Vendor ID on all invoices and correspondence.',
                    ...($note !== '' ? [$note] : []),
                    'Our team will be in touch about next steps. Welcome aboard.',
                ],
                [
                    'Vendor ID' => $application->vendor_code,
                    'Company' => $application->registered_name,
                    'Approved services' => implode(', ', $application->serviceLabels($application->approved_services ?? [])),
                    'Reference' => $application->reference,
                ],
            ],
            'rejected' => [
                'Application update',
                [
                    $greeting,
                    'Thank you for your interest in partnering with TravelWheel. After reviewing your application, we are unable to approve it at this time.',
                    ...($note !== '' ? ["Reason: $note"] : []),
                    'You are welcome to apply again in future if your circumstances change.',
                ],
                ['Reference' => $application->reference, 'Company' => $application->registered_name],
            ],
            default => [
                'More information needed',
                [
                    $greeting,
                    'Thank you for your application. Before we can continue our review, we need the following from you:',
                    $note,
                    'Please reply to this email with the information or documents, quoting your reference.',
                ],
                ['Reference' => $application->reference, 'Company' => $application->registered_name],
            ],
        };

        return new Content(view: 'emails.vendor-application', with: compact('heading', 'paragraphs', 'rows'));
    }
}
