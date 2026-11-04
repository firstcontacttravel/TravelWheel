<?php

namespace App\Mail;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells staff a company has asked for a corporate account. */
class CompanyRequestReceivedMail extends Mailable
{
    public function __construct(public Company $company) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New corporate account request: '.$this->company->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.corporate.request-received', with: [
            'company' => $this->company,
            'url' => CompanyResource::getUrl('view', ['record' => $this->company], panel: 'admin'),
        ]);
    }
}
