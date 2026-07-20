<?php

namespace App\Mail;

use App\Models\SupportExtraLuggage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportExtraLuggageNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $support;

    /** @var array<int, string> */
    public $attachmentPaths;

    public function __construct(SupportExtraLuggage $support, array $attachmentPaths = [])
    {
        $this->support = $support;
        $this->attachmentPaths = $attachmentPaths;
    }

    public function build()
    {
        $clientName = $this->support->full_name ?? $this->support->email ?? 'Client';

        $email = $this->subject('New Extra Luggage Payment - ' . $clientName)
            ->view('emails.support-extra-luggage-notification')
            ->with([
                'name' => $clientName,
                'email' => $this->support->email,
                'phone' => $this->support->contact_number,
                'service' => 'Extra Luggage Request',
                'amount' => $this->support->amount,
                'reference' => $this->support->payment_reference,
                'airline' => $this->support->airline,
                'data_page' => $this->support->data_page,
                'ticket' => $this->support->ticket,
                'booking_source' => 'Extra Luggage Form',
                'additional_info' => 'N/A',
            ]);

        foreach ($this->attachmentPaths as $path) {
            if (is_file($path)) {
                $email->attach($path);
            }
        }

        return $email;
    }
}
