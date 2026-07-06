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
}
