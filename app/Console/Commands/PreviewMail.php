<?php

namespace App\Console\Commands;

use App\Models\FlightBooking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Renders every redesigned email to public/mail-preview/ with representative
 * sample data, so the whole set can be eyeballed side by side in a browser
 * without sending anything or touching real bookings.
 *
 * Output is gitignored and the command refuses to run in production — the
 * fixtures below are fake, but the directory sits under the document root.
 */
class PreviewMail extends Command
{
    protected $signature = 'mail:preview';

    protected $description = 'Render the flight and visa emails to public/mail-preview for visual review';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to write previews into a production document root.');

            return self::FAILURE;
        }

        $dir = public_path('mail-preview');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $rendered = [];
        $failed = [];

        foreach ($this->samples() as $name => [$view, $data]) {
            try {
                file_put_contents($dir.'/'.$name.'.html', view($view, $data)->render());
                $rendered[$name] = $view;
                $this->line("  <fg=green>ok</> {$name}");
            } catch (Throwable $e) {
                $failed[$name] = $e->getMessage();
                $this->line("  <fg=red>fail</> {$name} — ".$e->getMessage());
            }
        }

        file_put_contents($dir.'/index.html', $this->index($rendered, $failed));
        $this->newLine();
        $this->info(count($rendered).' rendered, '.count($failed).' failed → /mail-preview/index.html');

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    private function samples(): array
    {
        $booking = $this->booking();

        return [
            'flight-eticket-ticketed' => ['mail.eticket', [
                'booking' => $booking, 'bookingRef' => $booking->booking_ref, 'isTicketed' => true,
                'ticketPNR' => 'KQ7X2M', 'airline' => 'Virgin Atlantic Airways', 'tripLabel' => 'Round trip',
                'cabin' => 'Economy', 'currencySymbol' => 'NGN ', 'totalAmount' => 840521.30,
                'passengers' => $booking->passengers_snapshot, 'awaitingSupplierTicket' => false,
            ]],
            'flight-eticket-awaiting' => ['mail.eticket', [
                'booking' => $booking, 'bookingRef' => $booking->booking_ref, 'isTicketed' => false,
                'ticketPNR' => 'KQ7X2M', 'airline' => 'Virgin Atlantic Airways', 'tripLabel' => 'Round trip',
                'cabin' => 'Economy', 'currencySymbol' => 'NGN ', 'totalAmount' => 840521.30,
                'passengers' => $booking->passengers_snapshot, 'awaitingSupplierTicket' => true,
            ]],
            'flight-booking-pending-hold' => ['emails.booking-pending', [
                'booking' => $booking, 'paymentMethod' => 'hold', 'isHoldNotice' => true,
                'isBankTransferNotice' => false, 'resumePaymentUrl' => 'https://travelwheel.ng/flights/payment/resume/abc123',
            ]],
            'flight-booking-pending-transfer' => ['emails.booking-pending', [
                'booking' => $booking, 'paymentMethod' => 'bank_transfer', 'isHoldNotice' => false,
                'isBankTransferNotice' => true, 'resumePaymentUrl' => null,
            ]],
            'flight-payment-receipt' => ['emails.payment-receipt', ['booking' => $booking]],
            'flight-unticketed-alert' => ['emails.unticketed-confirmation-alert', ['data' => [
                'uniqueId' => 'TW-8F3K2A', 'bookingStatus' => 'CONFIRMED', 'ticketStatus' => 'NOT_TICKETED',
                'timestamp' => Carbon::parse('2026-09-21 09:42:00'), 'origin' => 'LOS', 'destination' => 'LHR',
                'fareType' => 'Public', 'passengers' => $booking->passengers_snapshot,
            ]]],
            'travelflex-status-approved' => ['emails.travelflex-status', [
                'application' => $this->flexApplication(), 'status' => 'approved', 'note' => null,
                'paymentUrl' => 'https://travelwheel.ng/flights/travelflex/pay/abc123',
                'paymentDeadline' => Carbon::parse('2026-09-24 17:00:00'),
            ]],
            'travelflex-status-rejected' => ['emails.travelflex-status', [
                'application' => $this->flexApplication(), 'status' => 'rejected',
                'note' => 'The bank statement supplied did not cover the full six-month period.',
                'paymentUrl' => null, 'paymentDeadline' => null,
            ]],
            'travelflex-repayment-reminder' => ['emails.travelflex-repayment-reminder', [
                'application' => $this->flexApplication(),
                'instalment' => ['label' => 'Instalment 2 of 4', 'amount' => 187500, 'due_date' => '05 Oct 2026'],
                'timing' => 'in 5 days',
            ]],
            'travelflex-ticket-booked' => ['emails.travelflex-ticket-booked', ['booking' => $booking]],
            'travelflex-provider-review' => ['emails.travelflex-provider-review', $this->providerReview()],
            'visa-portal-access-code' => ['emails.visa-portal-access-code', [
                'application' => (object) ['reference' => 'VW-2026-00418'], 'code' => '481902',
            ]],
        ];
    }

    private function booking(): FlightBooking
    {
        return new FlightBooking([
            'booking_ref' => 'TW-8F3K2A',
            'route' => 'Lagos (LOS) → London (LHR)',
            'airline' => 'Virgin Atlantic Airways',
            'total_price' => 840521.30,
            'currency' => 'NGN',
            'payment_currency' => 'NGN',
            'payment_amount' => 840521.30,
            'payment_charged_amount' => 853129.12,
            'payment_reference' => 'SBT-2026-99120455',
            'payment_gateway' => 'seerbit',
            'payment_verified_at' => '2026-09-19 14:08:00',
            'tkt_time_limit' => '2026-09-23 18:00:00',
            'passengers_snapshot' => [
                ['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Adebayo', 'last_name' => 'Okonkwo', 'eticket' => '932-4410882751', 'email' => 'adebayo@example.com', 'nationality' => 'Nigerian'],
                ['type' => 'CHD', 'title' => 'Miss', 'first_name' => 'Ada', 'last_name' => 'Okonkwo', 'eticket' => '932-4410882752', 'email' => 'adebayo@example.com', 'nationality' => 'Nigerian'],
            ],
            'flight_snapshot' => [
                'cabin' => 'Economy', 'airline' => 'Virgin Atlantic Airways',
                'segments' => [['from' => 'LOS', 'to' => 'LHR', 'departDT' => '2026-09-25T10:10:00']],
            ],
            'extra_services_snapshot' => [
                'baggage' => [['description' => 'Extra checked bag 23kg', 'quantity' => 1, 'line_total' => 48000]],
                'meal' => [['description' => 'Vegetarian meal', 'unit_price' => 9500]],
            ],
        ]);
    }

    private function flexApplication(): object
    {
        return (object) [
            'booking_ref' => 'TW-8F3K2A',
            'applicant_details' => ['full_name' => 'Adebayo Okonkwo'],
            'grand_total' => 940000,
            'down_payment' => 300000,
            'payment_status' => 'awaiting_down_payment',
            'repayment_plan' => ['administration_fee' => 9400, 'insurance_fee' => 14100, 'upfront_payment_total' => 323500],
        ];
    }

    /** @return array<string, mixed> */
    private function providerReview(): array
    {
        return [
            'bookingRef' => 'TW-8F3K2A',
            'applicant' => [
                'applicant_type' => 'individual', 'full_name' => 'Adebayo Okonkwo',
                'email' => 'adebayo@example.com', 'phone_primary' => '+234 803 111 2233',
                'home_address' => '12 Adeola Odeku Street, Victoria Island, Lagos',
                'occupation' => 'Software Engineer', 'sector' => 'private_sector',
                'employer_name' => 'Northbridge Systems Ltd', 'employer_address' => '5 Kofo Abayomi, Lagos',
                'staff_number' => 'NB-44120', 'office_id' => 'VI-2', 'bvn' => '22XXXXXXXX1',
                'nin' => '10XXXXXXXX4', 'passport_number' => 'A0XXXXXX9',
            ],
            'flightInfo' => [
                'currency' => 'NGN', 'airline' => 'Virgin Atlantic Airways', 'price' => 840521.30,
                'segments' => [['from' => 'LOS', 'to' => 'LHR']],
            ],
            'loanPlan' => [
                'ticket_cost' => 840521.30, 'grand_total' => 940000, 'down_payment' => 300000,
                'down_percent' => 36, 'administration_fee' => 9400, 'insurance_fee' => 14100,
                'upfront_payment_total' => 323500, 'loan_amount' => 540521.30, 'total_interest' => 37500,
                'repayment_plan' => 'four_months', 'payment_method' => 'bank_transfer',
                'schedule' => [
                    ['label' => 'Instalment 1', 'due_date' => '05 Sep 2026', 'amount' => 187500],
                    ['label' => 'Instalment 2', 'due_date' => '05 Oct 2026', 'amount' => 187500],
                    ['label' => 'Instalment 3', 'due_date' => '05 Nov 2026', 'amount' => 187500],
                ],
            ],
            'uploadPaths' => ['valid_id' => 'uploads/id.pdf', 'passport_photo' => 'uploads/photo.jpg', 'bank_statements' => 'uploads/bank.pdf'],
        ];
    }

    /** @param array<string, string> $rendered @param array<string, string> $failed */
    private function index(array $rendered, array $failed): string
    {
        $items = '';
        foreach ($rendered as $name => $view) {
            $items .= '<li><a href="'.$name.'.html">'.$name.'</a> <code>'.$view.'</code></li>';
        }
        foreach ($failed as $name => $error) {
            $items .= '<li style="color:#b42318">'.$name.' — '.e($error).'</li>';
        }

        return '<!doctype html><meta charset="utf-8"><title>Mail previews</title>'
            .'<style>body{font:15px/1.7 system-ui,sans-serif;max-width:720px;margin:48px auto;padding:0 20px;color:#111827}'
            .'h1{font-size:20px}li{margin:6px 0}code{color:#667085;font-size:12px}a{color:#303191}</style>'
            .'<h1>Flight &amp; visa email previews</h1><ul>'.$items.'</ul>';
    }
}
