<?php

namespace App\Mail;

use App\Models\CompanyInvitation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** The single-use link to join a company's corporate account. */
class CompanyInvitationMail extends Mailable
{
    public function __construct(
        public CompanyInvitation $invitation,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your invitation to '.$this->invitation->company->name.' on TravelWheel Corporate');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.corporate.invitation', with: [
            'name' => $this->invitation->name,
            'companyName' => $this->invitation->company->name,
            'url' => route('business.invitation.show', $this->token),
            'expiresOn' => $this->invitation->expires_at->timezone('Africa/Lagos')->format('j F Y'),
            'isAdmin' => $this->invitation->role === 'admin',
        ]);
    }
}
