<?php

namespace App\Mail;

use App\Models\FlightBooking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells an account manager that one of their company's bookings needs attention. */
class CompanyBookingProblemMail extends Mailable
{
    public function __construct(
        public FlightBooking $booking,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->booking->company->name.': booking '.$this->booking->booking_ref.' needs attention');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.corporate.booking-problem', with: [
            'booking' => $this->booking,
            'url' => $this->url,
        ]);
    }
}
