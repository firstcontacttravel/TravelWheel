<?php

namespace Tests\Feature;

use App\Livewire\Pages\FlightBooking;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Trip customisation step only has anything on it when the supplier
 * returns extra services, and TravelNext — the only supplier that does — is
 * IP-blocked from a developer machine. Without these, the populated state is
 * unreachable locally and only ever exercised in production.
 */
class FlightBookingCustomisationStepTest extends TestCase
{
    private function service(string $id, string $desc, float $amount, string $fare = 'per bag'): array
    {
        return [
            'ServiceId' => $id,
            'Description' => $desc,
            'FareDescription' => $fare,
            'MaximumQuantity' => 3,
            'ServiceCost' => ['Amount' => $amount, 'CurrencyCode' => 'NGN'],
        ];
    }

    private function seedFlight(array $extraServices = [], array $fareRules = []): void
    {
        session([
            'bookingFlight' => [
                'source' => 'travelnext',
                'currency' => 'NGN',
                'price' => 2659427.90,
                'baseFare' => 2569427.90,
                'totalTax' => 60000.0,
                'airline' => 'Virgin Atlantic Airways', 'airlineCode' => 'VS', 'cabinCode' => 'Y',
                'stops' => 0, 'isRefundable' => false,
                'totalTimeLabel' => '7h', 'departDateLabel' => 'Thu, 15 Oct 2026',
                'segments' => [[
                    'from' => 'LOS', 'to' => 'LHR', 'fromCity' => 'Lagos (LOS)', 'toCity' => 'London (LHR)',
                    'fromAirport' => 'Murtala Muhammed Airport', 'toAirport' => 'Heathrow Airport',
                    'airline' => 'Virgin Atlantic Airways', 'airlineCode' => 'VS', 'flightNo' => 'VS412',
                    'departTime' => '10:30 am', 'arriveTime' => '05:30 pm',
                    'departDT' => '2026-10-15T10:30:00+00:00', 'duration' => 420,
                    'resBookCode' => 'N', 'cabin' => 'Economy', 'seatsLeft' => 9,
                ]],
                'returnSegments' => [], 'multiLegs' => [],
                'fareBreakdown' => [[
                    'passengerType' => 'ADT', 'qty' => 3, 'baseFare' => 856475.97, 'totalFare' => 886475.97,
                    'baggage' => ['2 PC'], 'cabinBaggage' => ['1 PC'],
                ]],
            ],
            'bookingSearchParams' => ['adults' => 3, 'childs' => 0, 'kids' => 0, 'flight_type' => 'Y'],
            'bookingSessionId' => 'sess-customisation',
            'extraServices' => $extraServices,
            'fareRules' => $fareRules,
            'tripType' => 'oneway',
        ]);
    }

    public function test_it_renders_baggage_and_meal_options_with_one_unit_each(): void
    {
        $this->seedFlight(['ExtraServicesResponse' => ['ExtraServicesResult' => ['ExtraServicesData' => [
            'DynamicBaggage' => [[
                'Behavior' => 'PER_PAX_OUTBOUND',
                'Services' => [[$this->service('B1', 'Extra bag up to 23kg', 38500)]],
            ]],
            'DynamicMeal' => [[
                'Behavior' => 'PER_PAX_PER_SEGMENT_OUTBOUND',
                'Services' => [[$this->service('M1', 'Vegetarian hot meal', 9800, 'per traveller')]],
            ]],
        ]]]]);

        $html = Livewire::test(FlightBooking::class)->set('step', 2)->html();

        $this->assertStringContainsString('Extra checked baggage', $html);
        $this->assertStringContainsString('Extra bag up to 23kg', $html);
        $this->assertStringContainsString('Meal preferences', $html);
        $this->assertStringContainsString('Vegetarian hot meal', $html);

        // The step never said it was skippable, so travellers had no way to know
        // they could just continue.
        $this->assertStringContainsString('Nothing here is required', $html);

        // Every heading on this step used to be tracked-out capitals.
        $this->assertStringNotContainsString('EXTRA CHECK-IN BAGGAGE', $html);
        $this->assertStringNotContainsString('MEAL PREFERENCES', $html);
    }

    public function test_a_route_with_no_extras_says_what_the_fare_already_covers(): void
    {
        // SkyLink has no extra-services endpoint at all, so this is what every
        // SkyLink booking sees. It used to be a dead end that only reported an
        // absence: "No additional baggage options available for this route".
        $this->seedFlight();

        $html = Livewire::test(FlightBooking::class)->set('step', 2)->html();

        $this->assertStringContainsString('No optional extras for this route', $html);
        $this->assertStringContainsString('2 PC', $html);
        $this->assertStringContainsString('1 PC', $html);
    }

    public function test_fare_rules_render_without_shouting_headings(): void
    {
        $this->seedFlight([], ['FareRules1_1Response' => ['FareRules1_1Result' => [
            'BaggageInfos' => [
                ['BaggageInfo' => ['FlightNo' => 'VS412', 'Departure' => 'LOS', 'Arrival' => 'LHR', 'Baggage' => '2 PC']],
            ],
            'FareRules' => [
                ['FareRule' => [
                    'Airline' => 'VS', 'CityPair' => 'LOSLHR', 'Category' => 'Penalties',
                    'Rules' => 'CHANGES<br>CHARGE NGN 85000 FOR REISSUE.',
                ]],
            ],
        ]]]);

        $html = Livewire::test(FlightBooking::class)->set('step', 2)->html();

        $this->assertStringContainsString('Fare and baggage rules', $html);
        $this->assertStringContainsString('Baggage allowance by flight', $html);
        $this->assertStringContainsString('Changes and cancellations', $html);
        $this->assertStringContainsString('CHARGE NGN 85000 FOR REISSUE.', $html);
        $this->assertStringNotContainsString('BAGGAGE ALLOWANCE BY SEGMENT', $html);
    }
}
