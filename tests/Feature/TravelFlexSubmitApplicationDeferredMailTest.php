<?php

namespace Tests\Feature;

use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Services\DurableMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TravelFlexSubmitApplicationDeferredMailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test for a production crash: submitting the TravelFlex
     * application form used to build the provider-handoff email (which
     * attaches a dompdf-rendered itinerary PDF) synchronously, inline in the
     * request. A slow render could hit PHP's max_execution_time — a fatal
     * error that bypasses try/catch entirely — leaving the customer looking
     * at a raw error page instead of the "application submitted" screen.
     *
     * The fix defers that email work to run after the response is already
     * built via dispatch(...)->afterResponse(). This test proves the redirect
     * to the pending page still happens even though the deferred provider
     * email work runs as part of the same request lifecycle in tests.
     */
    public function test_submitting_a_valid_application_redirects_to_pending_and_still_sends_the_provider_email(): void
    {
        Storage::fake('local');
        Mail::fake();

        $booking = FlightBooking::create([
            'booking_ref' => 'TW-SUBMIT-TEST',
            'unique_id' => 'HOLD-SUBMIT-TEST',
            'fare_source_code' => 'TEST-FARE-SOURCE',
            'fare_type' => 'Public',
            'booking_status' => 'on_hold',
            'payment_status' => 'pending',
            'tkt_time_limit' => now()->addHours(10),
            'contact_email' => 'traveller@example.com',
            'flight_snapshot' => [
                'price' => 100000,
                'segments' => [['departDT' => now()->addDays(30)->toIso8601String()]],
            ],
        ]);

        session([
            'bookingFlight' => [
                'flight' => [
                    'price' => 100000,
                    'fareType' => 'Public',
                    'isRefundable' => true,
                    'fareBreakdown' => [[
                        'passengerType' => 'ADT',
                        'qty' => 1,
                        'refundAllowed' => true,
                        'refundPenalty' => 10000,
                    ]],
                    'segments' => [['departDT' => now()->addDays(30)->toIso8601String()]],
                ],
            ],
            'selectedExtras' => [],
            'travelFlexPlan' => ['down_percent' => 30, 'repayment_plan' => '1 week'],
            'flightBookingDbId' => $booking->id,
            'bookingRef' => $booking->booking_ref,
            'bookingUniqueId' => $booking->unique_id,
        ]);

        $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $response = $this->post(route('flights.travelflex.submit-application'), [
            'applicant_type' => 'individual',
            'home_address' => '12 Broad Street, Lagos',
            'email' => 'traveller@example.com',
            'phone_primary' => '08012345678',
            'bvn' => '12345678901',
            'nin' => '10987654321',
            'title' => 'Mr',
            'surname' => 'Okafor',
            'first_name' => 'Chidi',
            'marital_status' => 'married',
            'gender' => 'male',
            'date_of_birth' => '1988-04-12',
            'passport_number' => 'A12345678',
            'passport_expiry_date' => now()->addYears(2)->toDateString(),
            'government_id_type' => 'national_id',
            'employer_name' => 'Okafor Ventures Limited',
            'employer_address' => '25 Marina Road, Lagos',
            'occupation' => 'Managing Director',
            'job_description' => 'Business owner',
            'office_id' => 'OVL-001',
            'sector' => 'private',
            'monthly_salary' => 850000,
            'salary_account_number' => '0123456789',
            'bank_name' => 'Example Bank',
            'next_of_kin_surname' => 'Okafor',
            'next_of_kin_first_name' => 'Ada',
            'next_of_kin_relationship' => 'Spouse',
            'next_of_kin_date_of_birth' => '1990-01-15',
            'next_of_kin_gender' => 'female',
            'next_of_kin_title' => 'Mrs',
            'next_of_kin_address' => '12 Broad Street, Lagos',
            'next_of_kin_phone_primary' => '08055555555',
            'next_of_kin_email' => 'ada@example.com',
            'fast_credit_agreement' => '1',
            'digital_signature' => 'Chidi Okafor',
            'digital_signature_image' => $signature,
            'witness_full_name' => 'Ada Okafor',
            'witness_signature_image' => $signature,
            'witness_declaration' => '1',
            'valid_id' => UploadedFile::fake()->create('valid_id.pdf', 100, 'application/pdf'),
            'passport_photo' => UploadedFile::fake()->image('passport.jpg'),
            'work_id_card' => UploadedFile::fake()->create('work_id.pdf', 100, 'application/pdf'),
            'employment_letter' => UploadedFile::fake()->create('employment_letter.pdf', 100, 'application/pdf'),
            'bank_statements' => UploadedFile::fake()->create('bank_statements.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('flights.travelflex.pending'));

        $this->assertDatabaseHas('notification_outboxes', [
            'kind' => DurableMailService::TRAVELFLEX_PROVIDER,
        ]);
        $this->assertDatabaseHas('notification_outboxes', [
            'kind' => DurableMailService::TRAVELFLEX_STATUS,
        ]);
        $this->assertGreaterThanOrEqual(1, NotificationOutbox::whereIn('kind', [
            DurableMailService::TRAVELFLEX_PROVIDER,
            DurableMailService::TRAVELFLEX_STATUS,
        ])->where('status', 'sent')->count());
    }
}
