<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Services\SkylinkFlightService;
use App\Services\TravelFlexRiskAssessmentService;
use App\Support\FlightMarkup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SkyLink's cancel_penalty / change_penalty, in the shape a live search
 * returned on 2026-09-29, become the fare breakdown's refund and change
 * penalties — per passenger — which TravelFlex's risk check needs.
 */
class SkylinkPenaltyMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FlightMarkup::forgetCachedConfiguration();
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
    }

    public function test_penalties_are_read_per_passenger_in_supplier_dollars(): void
    {
        $flight = $this->map($this->offer(), adults: 1);
        $adult = $flight['fareBreakdown'][0];

        // ₦225,600 / 1500, like every other SkyLink figure before markup.
        $this->assertTrue($adult['refundAllowed']);
        $this->assertSame(150.4, $adult['refundPenalty']);
        $this->assertTrue($adult['changeAllowed']);
        $this->assertSame(75.2, $adult['changePenalty']);

        // Back to naira once marked up, as the customer and TravelFlex see it.
        $this->assertSame(225600.0, FlightMarkup::apply($flight)['fareBreakdown'][0]['refundPenalty']);
    }

    public function test_every_passenger_type_carries_the_penalty(): void
    {
        $flight = $this->map($this->offer(), adults: 2, children: 1);

        $this->assertSame(['ADT', 'CHD'], array_column($flight['fareBreakdown'], 'passengerType'));
        $this->assertSame([150.4, 150.4], array_column($flight['fareBreakdown'], 'refundPenalty'));
    }

    public function test_a_refundable_skylink_fare_now_passes_travelflex_with_the_penalty_per_passenger(): void
    {
        $flight = FlightMarkup::apply($this->map($this->offer(), adults: 2));

        $assessment = app(TravelFlexRiskAssessmentService::class)->assess($flight);

        $this->assertTrue($assessment['eligible'], $assessment['reason']);
        // Per passenger: two adults, two penalties.
        $this->assertSame(451200.0, $assessment['refund_penalty_total']);
    }

    public function test_a_non_refundable_offer_has_no_penalties(): void
    {
        $offer = $this->offer();
        unset($offer['cancel_penalty'], $offer['change_penalty']);
        $offer['segments'][0][0]['refundable'] = 0;

        $adult = $this->map($offer)['fareBreakdown'][0];

        $this->assertFalse($adult['refundAllowed']);
        $this->assertNull($adult['refundPenalty']);
        $this->assertNull($adult['changeAllowed']);
        $this->assertNull($adult['changePenalty']);
    }

    public function test_refund_not_allowed_outranks_a_refundable_leg(): void
    {
        $offer = $this->offer();
        $offer['cancel_penalty']['allowed'] = false;

        $adult = $this->map($offer)['fareBreakdown'][0];

        $this->assertFalse($adult['refundAllowed']);
        $this->assertNull($adult['refundPenalty']);
    }

    public function test_a_penalty_in_an_unexpected_currency_is_not_guessed(): void
    {
        $offer = $this->offer();
        $offer['cancel_penalty']['currency'] = 'EUR';

        $flight = FlightMarkup::apply($this->map($offer));

        $this->assertNull($flight['fareBreakdown'][0]['refundPenalty']);
        // …so TravelFlex still refuses rather than sizing a deposit on it.
        $this->assertFalse(app(TravelFlexRiskAssessmentService::class)->assess($flight)['eligible']);
    }

    private function map(array $offer, int $adults = 1, int $children = 0): array
    {
        return app(SkylinkFlightService::class)->mapSearchResult($offer, [
            'trip' => 'oneway',
            'adults' => $adults,
            'childs' => $children,
            'kids' => 0,
        ]);
    }

    /** A refundable Turkish Airlines offer, trimmed from a live search. */
    private function offer(): array
    {
        $depart = now()->addDays(45);

        return [
            'route_type' => 'SITI',
            'first_carrier' => 'TK',
            'price' => 739382,
            'actual_adult_base' => '225600.00',
            'actual_child_base' => '169200.00',
            'actual_infant_base' => '0.00',
            'currency' => 'NGN',
            'branded_fare' => 'Promotional',
            'fare_basis' => 'PB2XPBO',
            'refundable' => 1,
            'cancel_penalty' => ['allowed' => true, 'amount' => 225600, 'currency' => 'NGN', 'text' => 'Reissue/Refund maximum penalty amount for the ticket before departure'],
            'change_penalty' => ['allowed' => true, 'amount' => 112800, 'currency' => 'NGN', 'text' => 'Reissue/Refund maximum penalty amount for the ticket before departure'],
            'baggage_allowance' => ['checked' => '2 PC', 'cabin' => '8 KG'],
            'booking_token' => 'btk_live_shape',
            'segments' => [[[
                'img' => 'TK',
                'flight_no' => 'TK628',
                'airline' => 'Turkish Airlines',
                'class' => 'economy',
                'class_letter' => 'P',
                'departure_code' => 'LOS',
                'departure_time' => '10:05 pm',
                'departure_date' => $depart->format('d-m-Y'),
                'arrival_code' => 'IST',
                'arrival_time' => '07:15 am',
                'arrival_date' => $depart->copy()->addDay()->format('d-m-Y'),
                'duration_time' => '6h 10m',
                'seg_duration' => '6h 10m',
                'refundable' => 1,
            ]]],
        ];
    }
}
