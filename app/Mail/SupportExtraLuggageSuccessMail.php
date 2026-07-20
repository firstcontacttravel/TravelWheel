<?php

namespace App\Mail;

use App\Models\SupportExtraLuggage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportExtraLuggageSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $support;

    public function __construct(SupportExtraLuggage $support)
    {
        $this->support = $support;
    }

    public function build()
    {
        return $this->subject('Your Extra Luggage Payment Was Successful!')
            ->view('emails.support-extra-luggage-success')
            ->with([
                'name' => $this->support->full_name ?? 'Customer',
                'airline' => $this->support->airline ?? 'Selected Airline',
                'amount' => $this->support->amount,
                'reference' => $this->support->payment_reference,
                'service' => 'Extra Luggage Request',
            ]);
    }
}
