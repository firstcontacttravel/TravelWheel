<?php

namespace Tests\Unit;

use App\Support\FlightDisplay;
use PHPUnit\Framework\TestCase;

class FlightDisplayTest extends TestCase
{
    public function test_supplier_standard_bag_code_is_presented_as_seven_kilograms(): void
    {
        $this->assertSame('7KG', FlightDisplay::cabinBaggageLabel('SB'));
        $this->assertSame(['7KG', '10KG'], FlightDisplay::cabinBaggageValues(['SB', '10KG']));
    }

    public function test_other_cabin_baggage_values_are_preserved(): void
    {
        $this->assertSame('12KG', FlightDisplay::cabinBaggageLabel('12KG'));
    }

    public function test_multicity_route_is_built_from_each_leg(): void
    {
        $flight = [
            'segments' => [],
            'multiLegs' => [
                ['segments' => [['from' => 'LOS', 'to' => 'ABV']]],
                ['segments' => [['from' => 'ABV', 'to' => 'PHC']]],
                ['segments' => [['from' => 'PHC', 'to' => 'LOS']]],
            ],
        ];

        $this->assertSame('LOS → ABV → PHC → LOS', FlightDisplay::route($flight));
    }

    public function test_round_trip_route_includes_the_return_journey(): void
    {
        $flight = [
            'segments' => [['from' => 'LOS', 'to' => 'ABV']],
            'returnSegments' => [['from' => 'ABV', 'to' => 'LOS']],
        ];

        $this->assertSame('LOS → ABV → LOS', FlightDisplay::route($flight));
    }
}
