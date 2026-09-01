<?php

namespace Tests\Feature;

use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use App\Services\TravelFlexFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TravelFlexApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_application_cannot_collect_a_deposit(): void
    {
        [$booking, $application] = $this->makeApplication();

        $this->expectException(ValidationException::class);

        app(TravelFlexFlowService::class)->assertApprovedForDeposit($application);
    }

    public function test_approved_application_with_an_active_hold_can_collect_a_deposit(): void
    {
        [$booking, $application] = $this->makeApplication([
            'application_status' => 'approved',
            'financing_status' => 'approved',
            'deposit_status' => 'pending',
            'approval_expires_at' => now()->addHours(6),
        ]);

        $approvedBooking = app(TravelFlexFlowService::class)->assertApprovedForDeposit($application);

        $this->assertTrue($approvedBooking->is($booking));
    }

    public function test_approved_application_cannot_collect_a_deposit_inside_ticketing_buffer(): void
    {
        [, $application] = $this->makeApplication([
            'application_status' => 'approved',
            'financing_status' => 'approved',
            'deposit_status' => 'pending',
        ], [
            'tkt_time_limit' => now()->addMinutes(90),
        ]);

        $this->expectException(ValidationException::class);

        app(TravelFlexFlowService::class)->assertApprovedForDeposit($application);
    }

    public function test_ticketed_booking_cannot_collect_another_deposit(): void
    {
        [, $application] = $this->makeApplication([
            'application_status' => 'approved',
            'financing_status' => 'approved',
            'deposit_status' => 'pending',
        ], [
            'booking_status' => 'ticketed',
            'ticket_ordered' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(TravelFlexFlowService::class)->assertApprovedForDeposit($application);
    }

    public function test_approval_link_is_signed_and_never_outlives_the_hold_buffer(): void
    {
        [, $application] = $this->makeApplication([
            'application_status' => 'approved',
            'financing_status' => 'approved',
            'deposit_status' => 'pending',
            'approval_expires_at' => now()->addDay(),
        ], [
            'tkt_time_limit' => now()->addHours(8),
        ]);

        $url = app(TravelFlexFlowService::class)->approvalUrl($application);

        $this->assertNotNull($url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertTrue(app(TravelFlexFlowService::class)->approvalDeadline($application)->lessThanOrEqualTo(now()->addHours(6)));
    }

    public function test_bank_transfer_collects_deposit_then_fees_as_separate_steps(): void
    {
        Mail::fake();
        config()->set('travelwheel.travelflex_bank_accounts', [[
            'bank' => 'Test Bank',
            'account_number' => '1234567890',
            'account_name' => 'TravelWheel Test',
        ]]);

        $flight = [
            'price' => 100000,
            'currency' => 'NGN',
            'fareType' => 'Public',
            'isRefundable' => true,
            'departDT' => now()->addDays(30)->toIso8601String(),
            'fareBreakdown' => [[
                'passengerType' => 'ADT',
                'qty' => 1,
                'refundAllowed' => true,
                'refundPenalty' => 10000,
            ]],
        ];

        [$booking, $application] = $this->makeApplication([
            'application_status' => 'approved',
            'financing_status' => 'approved',
            // Covers applications approved before the fix, where pending did not
            // necessarily mean that a customer reference had been submitted.
            'deposit_status' => 'pending',
            'deposit_reference' => null,
            'fees_status' => 'not_due',
            'repayment_plan' => ['down_percent' => 30, 'repayment_plan' => '72 hours'],
            'applicant_details' => ['full_name' => 'Test Traveller', 'email' => 'traveller@example.com'],
        ], [
            'booking_status' => 'awaiting_deposit',
            'flight_snapshot' => $flight,
        ]);

        $session = [
            'travelFlexApplicationId' => $application->id,
            'travelFlexPlan' => ['down_percent' => 30, 'repayment_plan' => '72 hours'],
            'travelFlexApplicant' => ['full_name' => 'Test Traveller'],
            'flightBookingDbId' => $booking->id,
            'bookingFlight' => ['flight' => $flight],
            'bookingContact' => ['email' => 'traveller@example.com'],
            'bookingPassengers' => [],
            'selectedExtras' => [],
        ];

        $this->withSession($session)
            ->get(route('flights.travelflex.bank-transfer-form'))
            ->assertOk()
            ->assertSee('Step 1 of 2')
            ->assertSee('TravelFlex Down Payment');

        $this->post(route('flights.travelflex.bank-transfer'), ['payment_reference' => 'DEPOSIT-REF'])
            ->assertRedirect(route('flights.travelflex.bank-transfer-form'));

        $this->get(route('flights.travelflex.bank-transfer-form'))
            ->assertOk()
            ->assertSee('Step 2 of 2')
            ->assertSee('Administration &amp; Insurance Fees', false);

        $this->post(route('flights.travelflex.bank-transfer'), ['payment_reference' => 'FEES-REF'])
            ->assertRedirect(route('flights.travelflex.pending'));

        $this->get(route('flights.travelflex.pending'))
            ->assertOk()
            ->assertSee('Bank transfers submitted')
            ->assertSee('Verification pending');

        $application->refresh();
        $booking->refresh();

        $this->assertSame('DEPOSIT-REF', $application->deposit_reference);
        $this->assertSame('FEES-REF', $application->fees_reference);
        $this->assertSame('DEPOSIT-REF', $booking->bank_transfer_reference);
        $this->assertSame('30000.00', $booking->payment_amount);
        $this->assertSame('NGN', $booking->payment_currency);
        $this->assertSame('awaiting_bank_transfer', $booking->payment_status);
    }

    private function makeApplication(array $applicationOverrides = [], array $bookingOverrides = []): array
    {
        $booking = FlightBooking::create(array_merge([
            'booking_ref' => 'TW-FLEX-TEST',
            'unique_id' => 'HOLD-123',
            'fare_source_code' => 'TEST-FARE-SOURCE',
            'fare_type' => 'Public',
            'booking_status' => 'awaiting_approval',
            'payment_status' => 'pending',
            'tkt_time_limit' => now()->addHours(10),
            'contact_email' => 'traveller@example.com',
        ], $bookingOverrides));

        $application = TravelFlexApplication::create(array_merge([
            'flight_booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'application_status' => 'submitted',
            'financing_status' => 'pending',
            'deposit_status' => 'not_due',
            'payment_status' => 'not_due',
            'repayment_plan' => ['down_payment' => 30000],
        ], $applicationOverrides));

        return [$booking, $application];
    }
}
