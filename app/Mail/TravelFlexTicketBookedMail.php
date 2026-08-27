<?php

namespace App\Mail;

use App\Models\FlightBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelFlexTicketBookedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FlightBooking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Ticket Has Been Booked - '.($this->booking->booking_ref ?: 'TravelWheel'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travelflex-ticket-booked',
            with: [
                'booking' => $this->booking,
            ],
        );
    }
}
