<?php

namespace App\Mail;

use App\Filament\Resources\VendorApplications\VendorApplicationResource;
use App\Models\VendorApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells the team a new vendor application is waiting for review. Documents stay in the admin panel, not the inbox. */
class VendorApplicationSubmittedMail extends Mailable
{
    public function __construct(public VendorApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New vendor application: {$this->application->registered_name} ({$this->application->reference})",
            replyTo: [$this->application->contact_email],
        );
    }

    public function content(): Content
    {
        $application = $this->application;

        return new Content(view: 'emails.vendor-application', with: [
            'heading' => 'New vendor application',
            'paragraphs' => ['A new vendor / partner application has been submitted and is waiting for review.'],
            'rows' => [
                'Reference' => $application->reference,
                'Company' => $application->registered_name.($application->trading_name ? " (trading as {$application->trading_name})" : ''),
                'Country' => $application->country,
                'Business type' => implode(', ', $application->businessTypeLabels()),
                'Services' => implode(', ', $application->serviceLabels()),
                'Contact' => "{$application->contact_name}, {$application->contact_title}\n{$application->contact_email} / {$application->contact_phone}",
                'Documents uploaded' => (string) $application->documents()->count(),
            ],
            'buttonUrl' => VendorApplicationResource::getUrl('view', ['record' => $application], panel: 'admin'),
            'buttonLabel' => 'Review application',
        ]);
    }
}
