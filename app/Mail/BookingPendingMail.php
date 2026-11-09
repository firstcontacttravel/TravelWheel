<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesItineraryPdf;
use App\Models\FlightBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class BookingPendingMail extends Mailable
{
    use AttachesItineraryPdf, Queueable, SerializesModels;

    public function __construct(
        public FlightBooking $booking,
        public string $method = 'bank_transfer'
    )
    {}

    public function envelope(): Envelope
    {
        $ref = $this->booking->booking_ref ?? 'N/A';

        return new Envelope(
            subject: match ($this->method) {
                'pay_later' => 'Booking saved - pay to confirm - '.$ref,
                'pay_later_reminder' => 'Your saved booking expires soon - '.$ref,
                default => 'Booking Pending - '.$ref,
            },
        );
    }

    /** Held on our side only ("hold and pay later"): no seat at the airline. */
    private function isPayLater(): bool
    {
        return in_array($this->method, ['pay_later', 'pay_later_reminder'], true);
    }

    public function content(): Content
    {
        $resumePaymentUrl = null;

        if ($this->isPayLater()) {
            $resumePaymentUrl = $this->booking->booking_ref && $this->booking->tkt_time_limit?->isFuture()
                ? URL::temporarySignedRoute('flights.hold.resume', $this->booking->tkt_time_limit, ['bookingRef' => $this->booking->booking_ref])
                : null;
        } elseif ($this->booking->booking_ref && $this->booking->booking_status === 'on_hold') {
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
                'isHoldNotice' => $this->method === 'hold' || $this->isPayLater(),
                'isPayLater' => $this->isPayLater(),
                'isReminder' => $this->method === 'pay_later_reminder',
                'isBankTransferNotice' => $this->method === 'bank_transfer',
            ],
        );
    }

    public function attachments(): array
    {
        // A held booking the customer says they've paid by transfer isn't "unpaid".
        $state = $this->booking->booking_status === 'on_hold' && $this->booking->payment_status !== 'awaiting_bank_transfer'
            ? 'on_hold'
            : 'payment_pending';

        return [$this->itineraryAttachment($this->booking, state: $state)];
    }
}
