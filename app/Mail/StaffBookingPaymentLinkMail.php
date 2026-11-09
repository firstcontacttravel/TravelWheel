<?php

namespace App\Mail;

use App\Models\StaffBooking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Lets a customer pay for a booking staff made for them. */
class StaffBookingPaymentLinkMail extends Mailable
{
    public function __construct(public StaffBooking $staffBooking) {}

    public function envelope(): Envelope
    {
        $reservations = config('travelwheel.reservations_email');

        return new Envelope(
            from: new Address($reservations, 'TravelWheel Reservations'),
            replyTo: [new Address($reservations, 'TravelWheel Reservations')],
            subject: ($this->staffBooking->isVisa() ? 'Finish your TravelWheel visa application - ' : 'Complete your TravelWheel booking - ').$this->staffBooking->bookingReference(),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.staff-booking-payment-link', with: [
            'reference' => $this->staffBooking->bookingReference(),
            'visa' => $this->staffBooking->isVisa(),
            'product' => $this->staffBooking->productLabel(),
            'summary' => $this->staffBooking->summary(),
            'total' => $this->staffBooking->total(),
            'customer' => $this->staffBooking->customerName(),
            'payUrl' => $this->staffBooking->payUrl(),
        ]);
    }
}
