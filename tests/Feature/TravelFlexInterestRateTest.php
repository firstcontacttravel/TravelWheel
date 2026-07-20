<?php

namespace Tests\Feature;

use App\Http\Controllers\FlightBookingController;
use ReflectionMethod;
use Tests\TestCase;

class TravelFlexInterestRateTest extends TestCase
{
    public function test_server_calculates_and_snapshots_four_percent_interest(): void
    {
        config(['travelwheel.travelflex_interest_rate' => 0.04]);
        session([
            'bookingFlight' => [
                'flight' => [
                    'price' => 100000,
                    'fareType' => 'Public',
                    'isRefundable' => true,
                    'segments' => [['departDT' => now()->addDays(30)->toIso8601String()]],
                ],
            ],
            'selectedExtras' => [],
        ]);

        $method = new ReflectionMethod(FlightBookingController::class, '_normalizeTravelFlexPlan');
        $plan = $method->invoke(app(FlightBookingController::class), 30, '1 week', 'gateway');

        $this->assertSame(0.04, $plan['interest_rate']);
        $this->assertSame(4.0, $plan['interest_rate_percent']);
        $this->assertSame(2800.0, $plan['total_interest']);
        $this->assertSame(700.0, $plan['administration_fee']);
        $this->assertSame(1050.0, $plan['insurance_fee']);
        $this->assertSame(31750.0, $plan['upfront_payment_total']);
        $this->assertSame(104550.0, $plan['grand_total']);
    }
}
