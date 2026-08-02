<?php

namespace Tests\Feature;

use App\Http\Controllers\FlightBookingController;
use App\Services\TravelFlexRiskAssessmentService;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class TravelFlexRiskAssessmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'travelwheel.travelflex_minimum_down_payment_percent' => 30,
            'travelwheel.travelflex_maximum_down_payment_percent' => 90,
            'travelwheel.travelflex_down_payment_percentage_step' => 10,
            'travelwheel.travelflex_refund_processing_fee' => 10000,
            'travelwheel.travelflex_refund_risk_buffer_rate' => 0.05,
            'travelwheel.travelflex_refund_risk_buffer_fixed' => 0,
        ]);
    }

    public function test_it_rounds_the_risk_based_minimum_up_to_the_next_available_percentage(): void
    {
        $assessment = app(TravelFlexRiskAssessmentService::class)->assess(
            $this->flight(price: 500000, penalty: 80000, quantity: 2),
            ['baggage' => [['line_total' => 20000]]],
        );

        $this->assertTrue($assessment['eligible']);
        $this->assertSame(160000.0, $assessment['refund_penalty_total']);
        $this->assertSame(20000.0, $assessment['non_refundable_extras']);
        $this->assertSame(26000.0, $assessment['risk_buffer']);
        $this->assertSame(216000.0, $assessment['estimated_cancellation_cost']);
        $this->assertSame(50, $assessment['minimum_down_percent']);
        $this->assertSame(260000.0, $assessment['minimum_down_payment']);
    }

    public function test_the_thirty_percent_product_floor_still_applies_to_low_penalty_fares(): void
    {
        config(['travelwheel.travelflex_refund_processing_fee' => 0]);

        $assessment = app(TravelFlexRiskAssessmentService::class)->assess(
            $this->flight(price: 100000, penalty: 5000),
        );

        $this->assertTrue($assessment['eligible']);
        $this->assertSame(30, $assessment['minimum_down_percent']);
        $this->assertSame(30000.0, $assessment['minimum_down_payment']);
    }

    public function test_missing_penalty_data_fails_closed(): void
    {
        $flight = $this->flight(price: 100000, penalty: 10000);
        unset($flight['fareBreakdown'][0]['refundPenalty']);

        $assessment = app(TravelFlexRiskAssessmentService::class)->assess($flight);

        $this->assertFalse($assessment['eligible']);
        $this->assertStringContainsString('complete refundable-fare penalties', $assessment['reason']);
    }

    public function test_fares_requiring_more_than_ninety_percent_are_ineligible(): void
    {
        $assessment = app(TravelFlexRiskAssessmentService::class)->assess(
            $this->flight(price: 100000, penalty: 90000),
        );

        $this->assertFalse($assessment['eligible']);
        $this->assertStringContainsString('more than 90%', $assessment['reason']);
    }

    public function test_server_rejects_a_customer_percentage_below_the_calculated_minimum(): void
    {
        session([
            'bookingFlight' => ['flight' => $this->flight(price: 500000, penalty: 80000, quantity: 2)],
            'selectedExtras' => ['baggage' => [['line_total' => 20000]]],
        ]);

        $method = new ReflectionMethod(FlightBookingController::class, '_normalizeTravelFlexPlan');

        try {
            $method->invoke(app(FlightBookingController::class), 40, '1 week', 'gateway');
            $this->fail('A down payment below the risk-based minimum was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This fare requires a minimum 50% down payment to cover its estimated cancellation cost.',
                $exception->errors()['down_percent'][0],
            );
        }
    }

    public function test_customer_can_select_any_available_percentage_above_the_minimum(): void
    {
        session([
            'bookingFlight' => ['flight' => $this->flight(price: 500000, penalty: 80000, quantity: 2)],
            'selectedExtras' => ['baggage' => [['line_total' => 20000]]],
        ]);

        $method = new ReflectionMethod(FlightBookingController::class, '_normalizeTravelFlexPlan');
        $plan = $method->invoke(app(FlightBookingController::class), 70, '1 week', 'gateway');

        $this->assertSame(50, $plan['minimum_down_percent']);
        $this->assertSame(70, $plan['down_percent']);
        $this->assertSame(364000.0, $plan['down_payment']);
        $this->assertSame(216000.0, $plan['risk_assessment']['estimated_cancellation_cost']);
    }

    public function test_calculator_only_displays_the_calculated_minimum_and_higher_options(): void
    {
        $response = $this->withSession([
            'bookingFlight' => ['flight' => $this->flight(price: 500000, penalty: 80000, quantity: 2)],
            'selectedExtras' => ['baggage' => [['line_total' => 20000]]],
        ])->get(route('flights.travelflex'));

        $response
            ->assertOk()
            ->assertSee('50% (Minimum for this fare)')
            ->assertSee('<option value="60">60%</option>', false)
            ->assertDontSee('<option value="40">', false);
    }

    public function test_server_checks_the_final_instalment_against_the_departure_buffer(): void
    {
        config(['travelwheel.travelflex_refund_processing_fee' => 0]);
        $flight = $this->flight(price: 100000, penalty: 5000);
        $flight['segments'][0]['departDT'] = now()->addDays(50)->toIso8601String();

        session([
            'bookingFlight' => ['flight' => $flight],
            'selectedExtras' => [],
        ]);

        $method = new ReflectionMethod(FlightBookingController::class, '_normalizeTravelFlexPlan');

        $this->expectException(ValidationException::class);
        $method->invoke(app(FlightBookingController::class), 30, '2 months', 'gateway');
    }

    private function flight(float $price, float $penalty, int $quantity = 1): array
    {
        return [
            'price' => $price,
            'fareType' => 'Public',
            'isRefundable' => true,
            'fareBreakdown' => [[
                'passengerType' => 'ADT',
                'qty' => $quantity,
                'refundAllowed' => true,
                'refundPenalty' => $penalty,
            ]],
            'segments' => [['departDT' => now()->addDays(90)->toIso8601String()]],
        ];
    }
}
