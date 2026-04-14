<?php

namespace App\Mail;

use App\Models\FlightBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a booking is on hold, awaiting bank-transfer verification.
 * No PDF attachment — ticket hasn't been issued yet.
 * The e-ticket PDF email (BookingConfirmedMail) is sent once payment is verified
 * and ticket_order succeeds.
 */
class BookingPendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FlightBooking $booking,
        public readonly string        $paymentMethod = 'bank_transfer',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📬 Booking On Hold — ' . $this->booking->booking_ref . ' | TravelWheel',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-pending',
            with: [
                'booking'       => $this->booking,
                'paymentMethod' => $this->paymentMethod,
            ],
        );
    }

    public function attachments(): array
    {
        return []; // No PDF until ticket is issued
    }
}