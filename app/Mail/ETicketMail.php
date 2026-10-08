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

    private ?bool $ticketed = null;

    public function __construct(
        public readonly FlightBooking $booking,
        public readonly array $tripDetails = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->ticketed()
                ? 'Your E-Ticket - '.$this->booking->booking_ref.' | TravelWheel'
                : 'Booking confirmed - '.$this->booking->booking_ref.' | TravelWheel',
        );
    }

    /**
     * This email also goes out before any ticket exists: at SkyLink
     * reservation, and when a TravelNext booking is made but its ticket
     * numbers are not back yet. Titling those "Your E-Ticket" tells the
     * customer they hold something they don't. Subject, body and attachment
     * all follow the rule the PDF itself uses, so they can never disagree.
     */
    private function ticketed(): bool
    {
        return $this->ticketed ??= app(ItineraryPdfService::class)->isTicketed($this->booking, $this->tripDetails);
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
                'isTicketed' => $this->ticketed(),
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
                'error_type' => $e::class,
            ]);

            throw $e;
        }

        // Until a ticket exists the PDF is an itinerary, and its name says so.
        $prefix = $this->ticketed() ? 'eticket-' : 'booking-itinerary-';

        return [
            Attachment::fromData(
                fn () => $pdfBytes,
                $prefix.$this->booking->booking_ref.'.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
