<?php

namespace App\Mail;

use App\Filament\Resources\TravelFlexApplications\TravelFlexApplicationResource;
use App\Models\TravelFlexApplication;
use App\Services\TravelFlexRepaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To Fast Credit, copied to TravelWheel: an instalment is a week overdue and
 * the customer's automatic reminders have ended, so a person should follow up.
 *
 * This leaves the company, so it carries contact details and the instalment
 * only. BVN, bank details, employment and documents stay out.
 */
class TravelFlexOverdueAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TravelFlexApplication $application,
        public readonly array $instalment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TravelFlex repayment overdue: '.($this->application->booking_ref ?: 'TravelWheel').' · '.$this->label(),
        );
    }

    public function content(): Content
    {
        $repayments = app(TravelFlexRepaymentService::class);
        $schedule = collect($repayments->schedule($this->application));
        $dueDate = $repayments->dueDate($this->instalment);

        return new Content(
            view: 'emails.travelflex-overdue-alert',
            with: [
                'bookingRef' => $this->application->booking_ref,
                'customerName' => data_get($this->application->applicant_details, 'full_name')
                    ?: data_get($this->application->company_details, 'company_name')
                    ?: 'Not recorded',
                'customerEmail' => data_get($this->application->applicant_details, 'email')
                    ?: $this->application->booking?->contact_email
                    ?: 'Not recorded',
                'customerPhone' => data_get($this->application->applicant_details, 'phone_primary')
                    ?: $this->application->booking?->contact_phone
                    ?: 'Not recorded',
                'label' => $this->label(),
                'amount' => (float) ($this->instalment['amount'] ?? $this->instalment['total'] ?? $this->instalment['principal'] ?? 0),
                'dueLabel' => (string) ($this->instalment['dueDate'] ?? $dueDate?->toFormattedDateString() ?? '-'),
                'daysOverdue' => $dueDate ? (int) $dueDate->copy()->startOfDay()->diffInDays(now('Africa/Lagos')->startOfDay(), false) : null,
                'paidCount' => $schedule->where('status', TravelFlexRepaymentService::STATUS_PAID)->count(),
                'totalCount' => $schedule->count(),
                'adminUrl' => TravelFlexApplicationResource::getUrl('view', ['record' => $this->application]),
            ],
        );
    }

    private function label(): string
    {
        return (string) ($this->instalment['label'] ?? 'TravelFlex repayment');
    }
}
