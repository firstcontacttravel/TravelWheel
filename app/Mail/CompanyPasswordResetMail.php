<?php

namespace App\Mail;

use App\Models\CompanyUser;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CompanyPasswordResetMail extends Mailable
{
    public function __construct(
        public CompanyUser $user,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your TravelWheel Corporate password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.corporate.password-reset', with: [
            'name' => $this->user->name,
            'url' => route('business.password.reset', ['token' => $this->token, 'email' => $this->user->email]),
            'minutes' => (int) config('auth.passwords.company_users.expire'),
        ]);
    }
}
