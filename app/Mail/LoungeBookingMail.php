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
        public string $trans_id,
        public bool $isProviderLounge = false,
    ) {}

    public function build(): static
    {
        // LoungePair lounges have no self-serve pass generation — there's no
        // booking API, so an admin places the reservation on LoungePair's
        // site manually. That email sets expectations accordingly instead
        // of pointing the customer at a "Generate Pass" flow that doesn't
        // apply to them.
        $view = $this->isProviderLounge ? 'emails.lounge-pair-booking' : 'emails.lounge-booking';
        $subject = $this->isProviderLounge
            ? 'Thank You for Your Lounge Booking - TravelWheel'
            : 'Airport Lounge Booking Confirmation';

        return $this->from(config('mail.from.address', 'info@travelwheel.ng'), 'TravelWheel')
            ->subject($subject)
            ->markdown($view)
            ->with([
                'fullname' => $this->fullname,
                'trans_id' => $this->trans_id,
            ]);
    }
}
