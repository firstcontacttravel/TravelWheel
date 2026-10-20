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
        $headline = match (true) {
            ($this->alertData['supplier'] ?? '') === 'skylink' && ! ($this->alertData['reserved'] ?? true) => 'Paid Booking Not Reserved',
            ($this->alertData['supplier'] ?? '') === 'skylink' => 'SkyLink Booking Needs Ticketing',
            default => 'Booking Confirmed but Not Ticketed',
        };

        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'ALERT: '.$headline.' - '.$this->alertData['uniqueId'],
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            // Plain view, not markdown: the template now builds on the shared
            // <x-mail.layout> components rather than Laravel's markdown theme.
            view: 'emails.unticketed-confirmation-alert',
            with: ['data' => $this->alertData],
        );
    }

    public function attachments(): array
    {
        // By booking_ref first: uniqueId falls back to it for a booking that
        // never got a PNR (a SkyLink reservation that failed after payment).
        $uniqueId = $this->alertData['uniqueId'] ?? null;
        $bookingRef = data_get($this->alertData, 'pricing.booking_ref');
        $booking = FlightBooking::query()
            ->when($bookingRef, fn ($query) => $query->where('booking_ref', $bookingRef))
            ->when(! $bookingRef && $uniqueId, fn ($query) => $query->where('unique_id', $uniqueId))
            ->latest()
            ->first();

        return $booking
            ? [$this->itineraryAttachment($booking, state: 'ticketing_required', audience: 'internal')]
            : [];
    }
}
