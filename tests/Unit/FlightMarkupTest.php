<?php

namespace Tests\Unit;

use App\Support\FlightMarkup;
use PHPUnit\Framework\TestCase;

class FlightMarkupTest extends TestCase
{
    public function test_from_nigeria_economy_markup_is_added_once_per_booking(): void
    {
        $flight = FlightMarkup::apply([
            'price' => 100000,
            'cabinCode' => 'Y',
            'segments' => [
                ['fromCountry' => 'Nigeria', 'toCountry' => 'United Kingdom'],
            ],
        ]);

        $this->assertSame(30000.0, $flight['markupAmount']);
        $this->assertSame(130000.0, $flight['price']);
        $this->assertSame(100000.0, $flight['supplierPrice']);
        $this->assertSame('from_nigeria', $flight['markupCategory']);
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
}
