<?php

namespace App\Mail;

use App\Models\TravelFlexInstalmentPayment;
use App\Services\TravelFlexRepaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelFlexInstalmentPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TravelFlexInstalmentPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TravelFlex Payment Received - '.($this->payment->application->booking_ref ?: 'TravelWheel'),
        );
    }

    public function content(): Content
    {
        $application = $this->payment->application;
        $unpaid = collect(app(TravelFlexRepaymentService::class)->schedule($application))
            ->reject(fn (array $row): bool => $row['status'] === TravelFlexRepaymentService::STATUS_PAID);
        $next = $unpaid->first();

        return new Content(
            view: 'emails.travelflex-instalment-paid',
            with: [
                'bookingRef' => $application->booking_ref,
                'name' => data_get($application->applicant_details, 'full_name') ?: 'Traveller',
                'label' => $this->payment->label ?: 'TravelFlex repayment',
                'amount' => (float) $this->payment->amount,
                'paidOn' => $this->payment->paid_on->format('M j, Y'),
                'reference' => $this->payment->payment_reference,
                'remainingCount' => $unpaid->count(),
                'remainingAmount' => (float) $unpaid->sum('amount'),
                'nextLabel' => $next['label'] ?? null,
                'nextDue' => $next['due_label'] ?? null,
            ],
        );
    }
}
