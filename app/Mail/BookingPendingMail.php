<?php

namespace App\Mail;

use App\Models\FlightBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class BookingPendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FlightBooking $booking,
        public string $method = 'bank_transfer'
    )
    {
        \Log::info('BookingPendingMail instantiated', [
            'booking_id' => $booking->id ?? null,
            'method' => $method
        ]);
    }

    public function envelope(): Envelope
    {
        \Log::info('BookingPendingMail envelope method called');
        
        return new Envelope(
            subject: 'Booking Pending - ' . ($this->booking->booking_ref ?? 'N/A'),
        );
    }

    public function content(): Content
    {
        \Log::info('BookingPendingMail content method called');

        $resumePaymentUrl = null;

        if ($this->booking->booking_ref && $this->booking->booking_status === 'on_hold') {
            $resumePaymentUrl = URL::temporarySignedRoute(
                'flights.payment.options.resume',
                $this->booking->tkt_time_limit ?? now()->addDays(7),
                ['bookingRef' => $this->booking->booking_ref]
            );
        }
        
        return new Content(
            view: 'emails.booking-pending',
            with: [
                'booking' => $this->booking,
                'paymentMethod' => $this->method,
                'resumePaymentUrl' => $resumePaymentUrl,
                'isHoldNotice' => $this->method === 'hold',
                'isBankTransferNotice' => $this->method === 'bank_transfer',
            ],
        );
    }
}
