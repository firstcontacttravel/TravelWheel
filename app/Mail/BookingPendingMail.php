<?php
// ── app/Mail/BookingPendingMail.php ───────────────────────────────────────────
// Send when a non-LCC booking is on hold awaiting bank transfer payment.

namespace App\Mail;

use App\Models\FlightBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FlightBooking $booking,
        public string        $paymentMethod = 'bank_transfer'  // 'bank_transfer' | 'pending_gateway'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Flight Booking is On Hold – ' . $this->booking->unique_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-pending',
        );
    }
}