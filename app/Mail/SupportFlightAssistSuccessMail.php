<?php

namespace App\Mail;

use App\Models\SupportFlightAssist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportFlightAssistSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $support;

    public function __construct(SupportFlightAssist $support)
    {
        $this->support = $support;
    }

    public function build()
    {
        return $this->subject('Payment Successful - Support Request Received')
            ->view('emails.support-flight-assist-success')
            ->with([
                'name' => $this->support->name_on_ticket ?? 'Customer',
                'service' => ucfirst(str_replace('_', ' ', $this->support->request_type)),
                'amount' => $this->support->amount,
                'reference' => $this->support->payment_reference,
            ]);
    }
}
