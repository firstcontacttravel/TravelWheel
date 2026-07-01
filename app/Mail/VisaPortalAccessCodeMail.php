<?php

namespace App\Mail;

use App\Models\VisaApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisaPortalAccessCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VisaApplication $application, public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your TravelWheel visa access code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.visa-portal-access-code');
    }
}
