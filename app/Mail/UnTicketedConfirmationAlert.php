<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UnTicketedConfirmationAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $alertData) {}

    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: '⚠️ ALERT: Booking Confirmed but Not Ticketed — ' . $this->alertData['uniqueId'],
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.unticketed-confirmation-alert',
            with: ['data' => $this->alertData],
        );
    }
}