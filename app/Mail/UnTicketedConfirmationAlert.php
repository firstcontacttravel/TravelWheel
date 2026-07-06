<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesItineraryPdf;
use App\Models\FlightBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UnTicketedConfirmationAlert extends Mailable
{
    use AttachesItineraryPdf, Queueable, SerializesModels;

    public function __construct(public array $alertData) {}

    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'ALERT: Booking Confirmed but Not Ticketed - ' . $this->alertData['uniqueId'],
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.unticketed-confirmation-alert',
            with: ['data' => $this->alertData],
        );
    }

    public function attachments(): array
    {
        $uniqueId = $this->alertData['uniqueId'] ?? null;
        $bookingRef = data_get($this->alertData, 'pricing.booking_ref');
        $booking = FlightBooking::query()
            ->when($uniqueId, fn ($query) => $query->where('unique_id', $uniqueId))
            ->when(! $uniqueId && $bookingRef, fn ($query) => $query->where('booking_ref', $bookingRef))
            ->latest()
            ->first();

        return $booking
            ? [$this->itineraryAttachment($booking, state: 'ticketing_required', audience: 'internal')]
            : [];
    }
}
