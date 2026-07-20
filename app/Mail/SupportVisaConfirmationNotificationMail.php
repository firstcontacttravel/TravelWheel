<?php

namespace App\Mail;

use App\Models\SupportVisaConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportVisaConfirmationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $support;

    /** @var array<int, string> */
    public $attachmentPaths;

    public function __construct(SupportVisaConfirmation $support, array $attachmentPaths = [])
    {
        $this->support = $support;
        $this->attachmentPaths = $attachmentPaths;
    }

    public function build()
    {
        $email = $this->subject('New Visa Confirmation Payment - ' . ($this->support->full_name ?? $this->support->email ?? 'Client'))
            ->view('emails.support-visa-confirmation-notification')
            ->with([
                'name' => $this->support->full_name ?? $this->support->email ?? 'Client',
                'email' => $this->support->email,
                'phone' => $this->support->phone_number,
                'visa_file' => $this->support->visa_file,
                'service' => 'Visa Confirmation Request',
                'amount' => $this->support->amount,
                'reference' => $this->support->payment_reference,
                'additional_info' => $this->support->additional_info ?? 'N/A',
                'booking_source' => 'Form Submission',
            ]);

        foreach ($this->attachmentPaths as $path) {
            if (is_file($path)) {
                $email->attach($path);
            }
        }

        return $email;
    }
}
