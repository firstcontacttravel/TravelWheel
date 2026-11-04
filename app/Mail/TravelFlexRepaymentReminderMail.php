<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesItineraryPdf;
use App\Models\TravelFlexApplication;
use App\Services\TravelFlexApplicationService;
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
        public readonly string $stage,
    ) {}

    public function envelope(): Envelope
    {
        $topic = match ($this->stage) {
            TravelFlexApplicationService::STAGE_OVERDUE_FIRST => 'TravelFlex Repayment Overdue',
            TravelFlexApplicationService::STAGE_OVERDUE_FINAL => 'Final Reminder: TravelFlex Repayment Overdue',
            default => 'TravelFlex Repayment Reminder',
        };

        return new Envelope(
            subject: $topic.' - '.($this->application->booking_ref ?: 'TravelWheel'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travelflex-repayment-reminder',
            with: [
                'application' => $this->application,
                'instalment' => $this->instalment,
                'stage' => $this->stage,
            ],
        );
    }

    public function attachments(): array
    {
        $booking = $this->application->booking;

        return $booking ? [$this->itineraryAttachment($booking)] : [];
    }
}
