<?php

namespace App\Mail;

use App\Models\TravelFlexApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelFlexStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TravelFlexApplication $application,
        public readonly string $status,
        public readonly ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->status) {
                'reviewed' => 'TravelFlex Application Under Review - ' . ($this->application->booking_ref ?: 'TravelWheel'),
                'approved' => 'TravelFlex Application Approved - ' . ($this->application->booking_ref ?: 'TravelWheel'),
                'rejected' => 'TravelFlex Application Update - ' . ($this->application->booking_ref ?: 'TravelWheel'),
                default => 'TravelFlex Application Update - ' . ($this->application->booking_ref ?: 'TravelWheel'),
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travelflex-status',
            with: [
                'application' => $this->application,
                'status' => $this->status,
                'note' => $this->note,
            ],
        );
    }
}
