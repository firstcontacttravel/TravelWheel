<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sends a prospective vendor the link to the registration form. */
class VendorInvitationMail extends Mailable
{
    public function __construct(
        public ?string $contactName,
        public ?string $companyName,
        public ?string $personalMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation to become a TravelWheel partner', replyTo: [config('vendor_onboarding.notify_email')]);
    }

    public function content(): Content
    {
        $paragraphs = [
            'Dear '.($this->contactName ?: 'Partner').',',
            'TravelWheel would like to invite '.($this->companyName ?: 'your company').' to become an approved partner on our travel platform.',
            ...(filled($this->personalMessage) ? [$this->personalMessage] : []),
            'To get started, please complete our vendor registration form using the button below. It takes about 15–20 minutes. Please have your company registration documents, rate sheet, terms & conditions and any licences ready to upload.',
        ];

        return new Content(view: 'emails.vendor-application', with: [
            'heading' => 'Become a TravelWheel partner',
            'paragraphs' => $paragraphs,
            'buttonUrl' => route('partners.register'),
            'buttonLabel' => 'Complete the registration form',
        ]);
    }
}
