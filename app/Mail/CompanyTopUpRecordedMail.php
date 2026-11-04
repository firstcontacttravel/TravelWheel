<?php

namespace App\Mail;

use App\Filament\Resources\CompanyWalletTopUps\CompanyWalletTopUpResource;
use App\Models\CompanyWalletTopUp;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells staff a company says it has sent a bank transfer. */
class CompanyTopUpRecordedMail extends Mailable
{
    public function __construct(public CompanyWalletTopUp $topUp) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Wallet top-up to confirm: '.$this->topUp->company->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.corporate.top-up-recorded', with: [
            'topUp' => $this->topUp,
            'url' => CompanyWalletTopUpResource::getUrl('index', panel: 'admin'),
        ]);
    }
}
