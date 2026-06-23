<?php

namespace App\Mail;

use App\Models\FlightBooking;
use App\Services\ETicketPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FlightBooking $booking,
        public readonly array $tripDetails = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your E-Ticket is Ready - ' . $this->booking->booking_ref . ' | TravelWheel',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
            with: [
                'booking' => $this->booking,
                'tripDetails' => $this->tripDetails,
            ],
        );
    }

    public function attachments(): array
    {
        if (empty($this->tripDetails)) {
            return [];
        }

        $pdfService = app(ETicketPdfService::class);
        $pdfBytes = $pdfService->generate($this->booking, $this->tripDetails);

        return [
            Attachment::fromData(
                fn () => $pdfBytes,
                'eticket-' . $this->booking->booking_ref . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
