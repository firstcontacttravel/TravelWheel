<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoungeBookingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullname,
        public string $email,
    ) {}

    public function build(): static
    {
        return $this->from(config('mail.from.address', 'info@travelwheel.ng'), 'TravelWheel')
            ->subject('Airport Lounge Booking Confirmation')
            ->markdown('emails.lounge-booking')
            ->with([
                'fullname' => $this->fullname,
                'email'    => $this->email,
            ]);
    }
}
