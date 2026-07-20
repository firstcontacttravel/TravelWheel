<?php

namespace App\Mail;

use App\Models\SupportVisaConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportVisaConfirmationSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $support;

    public function __construct(SupportVisaConfirmation $support)
    {
        $this->support = $support;
    }

    public function build()
    {
        return $this->subject('Your Visa Confirmation Payment Was Successful!')
            ->view('emails.support-visa-confirmation-success')
            ->with([
                'name' => $this->support->full_name ?? 'Customer',
                'amount' => $this->support->amount,
                'reference' => $this->support->payment_reference,
            ]);
    }
}
