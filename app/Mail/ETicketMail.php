<?php

namespace App\Mail;

use App\Models\FlightBooking;
use App\Services\ETicketPdfService;
use App\Services\ItineraryPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ETicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FlightBooking $booking,
        public readonly array $tripDetails = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your E-Ticket - ' . $this->booking->booking_ref . ' | TravelWheel',
        );
    }

    public function content(): Content
    {
        $pdfService = app(ETicketPdfService::class);
        $viewData = $pdfService->buildViewData($this->booking, $this->tripDetails);

        return new Content(
            view: 'mail.eticket',
            with: array_merge($viewData, [
                'booking' => $this->booking,
                'bookingRef' => $this->booking->booking_ref,
                'isTicketed' => strtoupper($this->tripDetails['TicketStatus'] ?? '') === 'TICKETED',
                'tripDetails' => $this->tripDetails,
            ]),
        );
    }

    public function attachments(): array
    {
        try {
            Log::info('[ETicketMail] attachment build start', [
                'booking_id' => $this->booking->id,
                'booking_ref' => $this->booking->booking_ref,
            ]);

            $pdfService = app(ItineraryPdfService::class);
            $pdfBytes = $pdfService->generate($this->booking, $this->tripDetails, 'ticketed');

            Log::info('[ETicketMail] attachment pdf generated', [
                'booking_ref' => $this->booking->booking_ref,
                'size_bytes' => strlen($pdfBytes),
            ]);
        } catch (\Throwable $e) {
            Log::error('[ETicketMail] attachment generation failed', [
                'booking_id' => $this->booking->id,
                'booking_ref' => $this->booking->booking_ref,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return [
            Attachment::fromData(
                fn () => $pdfBytes,
                'eticket-' . $this->booking->booking_ref . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
