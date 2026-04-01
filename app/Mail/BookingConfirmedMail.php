<?php
// ── app/Mail/BookingConfirmedMail.php ─────────────────────────────────────────
// Send when booking is fully ticketed / confirmed.

namespace App\Mail;

use App\Models\FlightBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FlightBooking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Confirmed! Your e-Ticket – ' . $this->booking->unique_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
        );
    }
}