<?php

namespace Tests\Feature;

use App\Models\FlightBooking;
use App\Models\TravelFlexApplication;
use App\Services\TravelFlexFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
