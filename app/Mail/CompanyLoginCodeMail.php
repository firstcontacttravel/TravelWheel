<?php

namespace App\Mail;

use App\Models\CompanyUser;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CompanyLoginCodeMail extends Mailable
{
    public function __construct(
        public CompanyUser $user,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        // The code stays out of the subject, where phone lock screens would show it.
        return new Envelope(subject: 'Your TravelWheel Corporate sign-in code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.corporate.login-code', with: [
            'name' => $this->user->name,
            'code' => $this->code,
            'minutes' => (int) config('corporate.login_code.minutes'),
        ]);
    }
}
