<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\ETicketPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ETicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
        public readonly array   $tripDetails = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your E-Ticket — ' . $this->booking->booking_ref . ' | TravelWheel',
        );
    }

    public function content(): Content
    {
        // This renders the HTML email body (a simpler version of the PDF)
        return new Content(
            view: 'mail.eticket',
            with: [
                'booking'    => $this->booking,
                'bookingRef' => $this->booking->booking_ref,
                'isTicketed' => strtoupper($this->tripDetails['TicketStatus'] ?? '') === 'TICKETED',
                'tripDetails'=> $this->tripDetails,
            ],
        );
    }

    public function attachments(): array
    {
        // Generate the PDF on the fly and attach it
        $pdfService = app(ETicketPdfService::class);
        $pdfBytes   = $pdfService->generate($this->booking, $this->tripDetails);

        return [
            Attachment::fromData(
                fn() => $pdfBytes,
                'eticket-' . $this->booking->booking_ref . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
