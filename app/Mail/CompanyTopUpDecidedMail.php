<?php

namespace App\Mail;

use App\Models\CompanyWalletTopUp;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells a company's Admins whether a top-up reached the wallet. */
class CompanyTopUpDecidedMail extends Mailable
{
    public function __construct(public CompanyWalletTopUp $topUp) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->topUp->status === 'confirmed'
            ? 'Your TravelWheel wallet has been topped up'
            : 'Your TravelWheel wallet top-up was not confirmed');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.corporate.top-up-decided', with: [
            'topUp' => $this->topUp,
            'balance' => (float) $this->topUp->company->fresh()->wallet_balance,
            'url' => route('business.wallet.index'),
        ]);
    }
}
