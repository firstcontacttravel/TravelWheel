<?php

namespace App\Mail;

use App\Models\SupportYellowCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportYellowCardNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $support;

    /** @var array<int, string> */
    public $attachmentPaths;

    public function __construct(SupportYellowCard $support, array $attachmentPaths = [])
    {
        $this->support = $support;
        $this->attachmentPaths = $attachmentPaths;
    }

    public function build()
    {
        $serviceTypeMap = [
            'standard' => 'Standard (3 Days)',
            'fasttrack' => 'Fast Track (24 Hours)',
        ];
        $serviceTypeDisplay = $serviceTypeMap[$this->support->service_type] ?? $this->support->service_type;

        $email = $this->subject('New Yellow Card Application - ' . ($this->support->full_name ?? $this->support->email))
            ->view('emails.support-yellow-card-notification')
            ->with([
                'name' => $this->support->full_name,
                'email' => $this->support->email,
                'phone' => $this->support->phone_number,
                'service_type_display' => $serviceTypeDisplay,
                'data_page' => $this->support->data_page,
                'home_address' => $this->support->home_address,
                'delivery_address' => $this->support->delivery_address,
                'amount' => $this->support->amount,
                'reference' => $this->support->payment_reference,
            ]);

        foreach ($this->attachmentPaths as $path) {
            if (is_file($path)) {
                $email->attach($path);
            }
        }

        return $email;
    }
}
