<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesItineraryPdf;
use App\Models\TravelFlexApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelFlexRepaymentReminderMail extends Mailable
{
    use AttachesItineraryPdf, Queueable, SerializesModels;

    public function __construct(
        public readonly TravelFlexApplication $application,
        public readonly array $instalment,
        public readonly string $timing,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TravelFlex Repayment Reminder - ' . ($this->application->booking_ref ?: 'TravelWheel'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travelflex-repayment-reminder',
            with: [
                'application' => $this->application,
                'instalment' => $this->instalment,
                'timing' => $this->timing,
            ],
        );
    }

    public function attachments(): array
    {
        $booking = $this->application->booking;

        return $booking ? [$this->itineraryAttachment($booking)] : [];
    }
}
