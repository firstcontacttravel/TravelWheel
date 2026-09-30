<?php

namespace Tests\Unit;

use App\Models\ExchangeRate;
use App\Models\FlightServiceCharge;
use App\Support\FlightMarkup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightMarkupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1]);
        FlightMarkup::forgetCachedConfiguration();
    }

    public function test_from_nigeria_economy_charge_is_added_per_passenger(): void
    {
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'Y',
            'segments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom'],
            ],
            'fareBreakdown' => [
                ['passengerType' => 'ADT', 'qty' => 2],
                ['passengerType' => 'CHD', 'qty' => 1],
            ],
        ]);

        $this->assertSame(90000.0, $flight['markupAmount']);
        $this->assertSame(30000.0, $flight['markupRatePerPassenger']);
        $this->assertSame(3, $flight['markupPassengerCount']);
        $this->assertSame(190000.0, $flight['price']);
        $this->assertSame(100000.0, $flight['supplierPrice']);
        $this->assertSame('from_nigeria', $flight['markupCategory']);
    }

    public function test_the_cabin_name_decides_the_markup_not_a_booking_class_letter(): void
    {
        // SkyLink's cabinCode is the booking-class letter. W and P are sold as
        // Economy and Premium Economy; read as cabin codes they charged the
        // premium and first-class markup (seen live on 2026-09-29).
        $economyInW = FlightMarkup::apply(['price' => 100000, 'cabin' => 'Economy', 'cabinCode' => 'W',
            'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom']]]);
        $premiumInP = FlightMarkup::apply(['price' => 100000, 'cabin' => 'Premium Economy', 'cabinCode' => 'P',
            'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom']]]);

        $this->assertSame('economy', $economyInW['markupCabin']);
        $this->assertSame(30000.0, $economyInW['markupAmount']);
        $this->assertSame('premium_economy', $premiumInP['markupCabin']);
        $this->assertSame(60000.0, $premiumInP['markupAmount']);
    }

    public function test_a_trip_starting_in_nigeria_with_a_domestic_first_flight_is_from_nigeria(): void
    {
        $viaAbuja = FlightMarkup::apply(['price' => 100000, 'cabin' => 'Economy', 'segments' => [
            ['fromCountry' => 'Nigeria', 'toCountry' => 'Nigeria'],
            ['fromCountry' => 'Nigeria', 'toCountry' => 'Egypt'],
            ['fromCountry' => 'Egypt', 'toCountry' => 'United Arab Emirates'],
        ]]);

        $this->assertSame('from_nigeria', $viaAbuja['markupCategory']);
        $this->assertSame(30000.0, $viaAbuja['markupAmount']);
    }

    public function test_the_other_route_types_are_unchanged(): void
    {
        $route = fn (array $segments): string => FlightMarkup::routeCategory(['segments' => $segments]);

        $this->assertSame('domestic', $route([['fromCountry' => 'Nigeria', 'toCountry' => 'Nigeria']]));
        $this->assertSame('touches_nigeria', $route([['fromCountry' => 'United Kingdom', 'toCountry' => 'Nigeria']]));
        $this->assertSame('touches_nigeria', $route([
            ['fromCountry' => 'Ghana', 'toCountry' => 'Nigeria'],
            ['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom'],
        ]));
        $this->assertSame('not_nigeria', $route([['fromCountry' => 'United Kingdom', 'toCountry' => 'United States']]));
    }

    public function test_without_a_cabin_name_the_code_still_decides(): void
    {
        // TravelNext can leave the cabin name empty; its code is a real cabin code.
        $business = FlightMarkup::apply(['price' => 100000, 'cabin' => '', 'cabinCode' => 'C',
            'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom']]]);

        $this->assertSame('business', $business['markupCabin']);
        $this->assertSame(100000.0, $business['markupAmount']);
    }

    public function test_admin_configured_charge_is_used_for_every_passenger(): void
    {
        FlightServiceCharge::query()
            ->where('route_category', 'from_nigeria')
            ->where('cabin', 'economy')
            ->update(['amount' => 70000]);
        FlightMarkup::forgetCachedConfiguration();

        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'Y',
            'segments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom'],
            ],
            'fareBreakdown' => [
                ['passengerType' => 'ADT', 'qty' => 2],
            ],
        ]);

        $this->assertSame(140000.0, $flight['markupAmount']);
        $this->assertSame(240000.0, $flight['price']);
    }

    public function test_route_touching_nigeria_uses_inbound_markup(): void
    {
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'C',
            'segments' => [
                ['fromCountry' => 'United Kingdom', 'toCountry' => 'Nigeria'],
            ],
        ]);

        $this->assertSame(200000.0, $flight['markupAmount']);
        $this->assertSame(300000.0, $flight['price']);
        $this->assertSame('touches_nigeria', $flight['markupCategory']);
    }

    public function test_round_trip_starting_from_nigeria_uses_from_nigeria_markup(): void
    {
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'Y',
            'segments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom'],
            ],
            'returnSegments' => [
                ['fromCountry' => 'United Kingdom', 'toCountry' => 'Nigeria'],
            ],
        ]);

        $this->assertSame(30000.0, $flight['markupAmount']);
        $this->assertSame(130000.0, $flight['price']);
        $this->assertSame('from_nigeria', $flight['markupCategory']);
    }

    public function test_route_not_touching_nigeria_uses_world_markup(): void
    {
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabin' => 'First Class',
            'segments' => [
                ['fromCountry' => 'United Kingdom', 'toCountry' => 'United States'],
            ],
        ]);

        $this->assertSame(350000.0, $flight['markupAmount']);
        $this->assertSame(450000.0, $flight['price']);
        $this->assertSame('not_nigeria', $flight['markupCategory']);
    }

    public function test_domestic_route_uses_domestic_markup(): void
    {
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'Y',
            'segments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'Nigeria'],
            ],
        ]);

        $this->assertSame(20000.0, $flight['markupAmount']);
        $this->assertSame(120000.0, $flight['price']);
        $this->assertSame('domestic', $flight['markupCategory']);
    }

    public function test_domestic_round_trip_uses_domestic_markup(): void
    {
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'Y',
            'segments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'Nigeria'],
            ],
            'returnSegments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'Nigeria'],
            ],
        ]);

        $this->assertSame(20000.0, $flight['markupAmount']);
        $this->assertSame('domestic', $flight['markupCategory']);
    }

    public function test_domestic_leg_that_also_leaves_nigeria_is_not_domestic(): void
    {
        // One leg starts and ends in Nigeria, but the itinerary isn't
        // entirely domestic — the return leg leaves the country — so this
        // must not be priced as 'domestic'. It starts in Nigeria, so it is
        // "from Nigeria" (agreed 2026-09-29; it used to fall through to
        // "touches Nigeria" because only the first flight's destination was
        // checked).
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'Y',
            'segments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'Nigeria'],
            ],
            'returnSegments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom'],
            ],
        ]);

        $this->assertNotSame('domestic', $flight['markupCategory']);
        $this->assertSame('from_nigeria', $flight['markupCategory']);
    }

    public function test_multi_city_touching_nigeria_uses_touching_markup(): void
    {
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'S',
            'multiLegs' => [
                ['segments' => [['fromCountry' => 'Ghana', 'toCountry' => 'Nigeria']]],
                ['segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'Kenya']]],
            ],
        ]);

        $this->assertSame(120000.0, $flight['markupAmount']);
        $this->assertSame(220000.0, $flight['price']);
        $this->assertSame('touches_nigeria', $flight['markupCategory']);
    }

    public function test_applying_markup_twice_does_not_double_convert_fare_breakdown(): void
    {
        // SkyLink's _selectSkylinkFare() re-runs apply() on a flight whose
        // fareBreakdown already went through this exact USD->NGN conversion
        // once at search time (unlike TravelNext's own select() flow, which
        // rebuilds fareBreakdown fresh from raw revalidate data every time).
        // Without a guard, the second pass treats the already-NGN baseFare as
        // if it were still raw USD and multiplies by the rate again — found
        // live: a correct ~142,000 NGN became ~185,800,000 NGN (~1300x).
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);
        FlightMarkup::forgetCachedConfiguration();

        $flight = [
            'price' => 500,
            'cabinCode' => 'Y',
            'segments' => [['fromCountry' => 'Nigeria', 'toCountry' => 'Kenya']],
            'fareBreakdown' => [
                ['passengerType' => 'ADT', 'qty' => 1, 'baseFare' => 500, 'totalFare' => 500],
            ],
        ];

        $firstPass = FlightMarkup::apply($flight);
        $this->assertSame(750000.0, $firstPass['fareBreakdown'][0]['baseFare']);
        $this->assertSame('NGN', $firstPass['fareBreakdown'][0]['currency']);

        // Re-verify + re-price, same as select() does — everything else on
        // the flight changes, but fareBreakdown is carried over untouched.
        $secondPass = FlightMarkup::apply($firstPass);
        $this->assertSame(750000.0, $secondPass['fareBreakdown'][0]['baseFare']);

        $thirdPass = FlightMarkup::apply($secondPass);
        $this->assertSame(750000.0, $thirdPass['fareBreakdown'][0]['baseFare']);
    }
}
