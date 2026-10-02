<?php

namespace App\Mail;

use App\Models\InsurancePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the reservations team about every insurance purchase. Insurance
 * had no email at all, so a paid purchase whose Sanlam policy failed to
 * confirm could go unnoticed — those are flagged as needing follow-up.
 */
class InsurancePurchaseNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InsurancePurchase $purchase)
    {
    }

    public function build()
    {
        $failed = $this->purchase->status !== 'Successful';
        $name = trim(collect([$this->purchase->title, $this->purchase->firstname, $this->purchase->surname])->filter()->implode(' '));

        return $this->subject(($failed ? 'ACTION NEEDED: Insurance paid, policy not confirmed — ' : 'New Insurance Purchase — ').($name ?: $this->purchase->email))
            ->view('emails.insurance_purchase_notification')
            ->with([
                'failed' => $failed,
                'name' => $name,
                'purchase' => $this->purchase,
                'plan' => (int) $this->purchase->bookingtype_id === 2 ? 'Family' : 'Individual',
            ]);
    }
}
