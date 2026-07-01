<?php

namespace App\Mail;

use App\Models\VisaApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisaApplicationUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VisaApplication $application, public string $heading, public string $bodyText, public string $subjectLine) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.visa-application-update');
    }
}
