<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProtocolBookingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullname,
        public float  $amount,
        public string $trans_id,
    ) {}

    public function build(): static
    {
        return $this->from(config('mail.from.address', 'info@travelwheel.ng'), 'TravelWheel')
            ->subject('Protocol Service Booking Confirmation')
            ->markdown('emails.protocol-booking')
            ->with([
                'fullname' => $this->fullname,
                'amount'   => $this->amount,
                'trans_id' => $this->trans_id,
            ]);
    }
}
