<?php

namespace App\Mail;

use App\Models\LoungeBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Gives the reservations team the whole lounge booking form for every paid
 * booking. The customer's own confirmation only carries a name and a
 * reference, which isn't enough to arrange (or, for LoungePair, place) the
 * booking.
 */
class LoungeReservationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $form  the checkout form as submitted, for the fields the booking doesn't store
     */
    public function __construct(public LoungeBooking $booking, public array $form = []) {}

    public function build()
    {
        $booking = $this->booking;
        $lounge = $booking->lounge;
        $isLoungePair = $booking->provider === 'loungepair';
        $money = fn ($value) => (float) str_replace(',', '', (string) ($value ?? 0));

        return $this->subject(($isLoungePair ? 'ACTION NEEDED: Book on LoungePair — ' : 'New Lounge Booking — ').$booking->lounge_name.' — '.$booking->fullname)
            ->replyTo($booking->email, $booking->fullname)
            ->view('emails.lounge-reservation-notification')
            ->with([
                'booking' => $booking,
                'isLoungePair' => $isLoungePair,
                'where' => $lounge?->locationDetails(),
                'state' => $this->form['state'] ?? $lounge?->location,
                'infantAmount' => $money($this->form['infantAmount'] ?? 0),
                'totalPaid' => $money($this->form['p_amount'] ?? null) ?: ((float) $booking->amount + (float) $booking->vat),
            ]);
    }
}
