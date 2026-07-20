<?php
// ── app/Mail/TravelFlexApplicationMail.php ────────────────────────────────────
// Sent to the loan provider (+ CC to Travelwheel) when a user submits a
// TravelFlex loan application before any down payment is collected.

namespace App\Mail;

use App\Mail\Concerns\AttachesItineraryPdf;
use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use App\Services\TravelFlexApplicationPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelFlexApplicationMail extends Mailable
{
    use AttachesItineraryPdf, Queueable, SerializesModels;

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
        public ?TravelFlexApplication $application = null,
    ) {}

    public function envelope(): Envelope
    {
        $reference = $this->bookingRef ? ' - ' . $this->bookingRef : '';
        $applicant = $this->applicant['full_name'] ?? 'Applicant';

        return new Envelope(
            subject: 'TravelFlex Provider Review - ' . $applicant . $reference,
        );

    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travelflex-provider-review',
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
            'representative_valid_id' => 'Representative_Valid_ID',
            'cac_status_report' => 'CAC_Status_Report_Form_CAC_1_1',
            'share_certificate' => 'Share_Certificate',
            'memart' => 'MEMART',
            'register_of_members' => 'Register_Of_Members',
            'shareholders_agreement' => 'Shareholders_Agreement',
            'return_of_allotment' => 'Return_Of_Allotment_Form_CAC_2',
            'certificate_of_incorporation' => 'Certificate_Of_Incorporation',
            'board_resolution' => 'Board_Resolution_Authorization_Letter',
            'company_bank_statement' => 'Company_Bank_Statement',
            'tin_certificate' => 'TIN_Certificate',
        ];

        foreach ($this->uploadPaths as $key => $path) {
            if ($path && file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $attachments[] = Attachment::fromPath($path)
                    ->as(($labels[$key] ?? $key) . '_' . $this->bookingRef . '.' . $ext);
            }
        }

        if ($this->application) {
            $pdfBytes = app(TravelFlexApplicationPdfService::class)->generate($this->application);
            $reference = $this->bookingRef ?: ($this->application->booking_ref ?: $this->application->unique_id ?: 'application');

            $attachments[] = Attachment::fromData(
                fn () => $pdfBytes,
                'TravelFlex_Application_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $reference) . '.pdf'
            )->withMime('application/pdf');
        }

        $booking = FlightBooking::query()
            ->where('booking_ref', $this->bookingRef)
            ->orWhere('unique_id', $this->bookingRef)
            ->latest()
            ->first();

        if ($booking) {
            $attachments[] = $this->itineraryAttachment($booking, state: 'travelflex_review', audience: 'provider');
        }

        return $attachments;
    }
}
