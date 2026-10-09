<?php

namespace App\Mail;

use App\Models\VendorApplication;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells a vendor we have their application. */
class VendorApplicationReceivedMail extends Mailable
{
    public function __construct(public VendorApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "We've received your partner application ({$this->application->reference})");
    }

    public function content(): Content
    {
        $application = $this->application;

        return new Content(view: 'emails.vendor-application', with: [
            'heading' => 'Application received',
            'paragraphs' => [
                "Dear {$application->contact_name},",
                "Thank you for applying to become a TravelWheel partner. We've received your application and documents, and our team will now review them. We'll contact you if we need anything else, and let you know our decision by email.",
                'Please quote your reference in any correspondence.',
            ],
            'rows' => [
                'Reference' => $application->reference,
                'Company' => $application->registered_name,
                'Services' => implode(', ', $application->serviceLabels()),
                'Documents uploaded' => (string) $application->documents()->count(),
                'Submitted' => $application->declared_at->format('j M Y, g:i A'),
            ],
        ]);
    }
}
