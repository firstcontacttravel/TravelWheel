<?php

namespace App\Mail;

use App\Models\SupportFlightAssist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportFlightAssistNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $support;

    public function __construct(SupportFlightAssist $support)
    {
        $this->support = $support;
    }

    public function build()
    {
        return $this->subject('New Flight Assist Request - ' . ($this->support->name_on_ticket ?? 'Client'))
            ->view('emails.support-flight-assist-notification')
            ->with([
                'name' => $this->support->name_on_ticket ?? 'N/A',
                'email' => $this->support->email,
                'phone' => $this->support->phone,
                'service' => ucfirst(str_replace('_', ' ', $this->support->request_type)),
                'booking_source' => ucfirst($this->support->booking_source),
                'amount' => $this->support->amount,
                'reference' => $this->support->payment_reference,
                'additional_info' => $this->support->additional_info,
            ]);
    }
}
