<?php
// ── app/Mail/TravelFlexApplicationMail.php ────────────────────────────────────
// Sent to the loan provider (+ CC to Travelwheel) when a user submits a
// TravelFlex loan application and pays the down payment.

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelFlexApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array  $applicant   Validated applicant fields
     * @param array  $loanPlan    TravelFlex plan details (amount, schedule, etc.)
     * @param array  $flightInfo  Mapped flight data
     * @param array  $uploadPaths Absolute paths to uploaded files ['valid_id' => '/path', ...]
     * @param string $bookingRef  UniqueID from the booking API
     */
    public function __construct(
        public array  $applicant,
        public array  $loanPlan,
        public array  $flightInfo,
        public array  $uploadPaths,
        public string $bookingRef = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TravelFlex Loan Application — ' . ($this->applicant['full_name'] ?? 'Applicant') . ' — ' . $this->bookingRef,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travelflex-application',
        );
    }

    /** Attach all uploaded documents. */
    public function attachments(): array
    {
        $attachments = [];
        $labels = [
            'valid_id'          => 'Valid_ID',
            'passport_photo'    => 'Passport_Photo',
            'work_id_card'      => 'Work_ID_Card',
            'employment_letter' => 'Employment_Letter',
            'bank_statements'   => 'Bank_Statements',
        ];

        foreach ($this->uploadPaths as $key => $path) {
            if ($path && file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $attachments[] = Attachment::fromPath($path)
                    ->as(($labels[$key] ?? $key) . '_' . $this->bookingRef . '.' . $ext);
            }
        }

        return $attachments;
    }
}