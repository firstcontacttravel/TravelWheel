<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesItineraryPdf;
use App\Models\TravelFlexApplication;
use App\Services\TravelFlexFlowService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelFlexStatusMail extends Mailable
{
    use AttachesItineraryPdf, Queueable, SerializesModels;

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
        $paymentUrl = $this->status === 'approved'
            ? app(TravelFlexFlowService::class)->approvalUrl($this->application)
            : null;

        return new Content(
            view: 'emails.travelflex-status',
            with: [
                'application' => $this->application,
                'status' => $this->status,
                'note' => $this->note,
                'paymentUrl' => $paymentUrl,
                'paymentDeadline' => app(TravelFlexFlowService::class)->approvalDeadline($this->application),
            ],
        );
    }

    public function attachments(): array
    {
        $booking = $this->application->booking;
        if (! $booking) return [];

        $state = match ($this->status) {
            'approved' => 'travelflex_approved',
            'rejected' => 'travelflex_rejected',
            default => 'travelflex_review',
        };

        return [$this->itineraryAttachment($booking, state: $state)];
    }
}
