<?php

namespace Tests\Unit;

use App\Support\FlightMatch;
use PHPUnit\Framework\TestCase;

class FlightMatchTest extends TestCase
{
    public function test_the_same_flight_matches_across_supplier_formats(): void
    {
        // TravelNext: no offset, cabin code Y, flight number with the code.
        $travelnext = [
            'cabin' => 'Economy',
            'cabinCode' => 'Y',
            'segments' => [['airlineCode' => 'BA', 'flightNo' => 'BA75', 'departDT' => '2026-10-20T10:30:00']],
        ];
        // SkyLink: explicit offset, booking-class letter where the cabin code
        // would be, zero-padded number.
        $skylink = [
            'cabin' => 'Economy',
            'cabinCode' => 'O',
            'segments' => [['airlineCode' => 'BA', 'flightNo' => 'BA 075', 'departDT' => '2026-10-20T10:30:00+00:00']],
        ];

        $this->assertSame(FlightMatch::key($travelnext), FlightMatch::key($skylink));
    }

    public function test_a_different_departure_minute_is_a_different_flight(): void
    {
        $a = ['cabin' => 'Economy', 'segments' => [['airlineCode' => 'BA', 'flightNo' => 'BA75', 'departDT' => '2026-10-20T10:30:00']]];
        $b = ['cabin' => 'Economy', 'segments' => [['airlineCode' => 'BA', 'flightNo' => 'BA75', 'departDT' => '2026-10-20T10:35:00']]];

        $this->assertNotSame(FlightMatch::key($a), FlightMatch::key($b));
    }

    public function test_a_different_cabin_is_a_different_offer(): void
    {
        $segments = [['airlineCode' => 'BA', 'flightNo' => 'BA75', 'departDT' => '2026-10-20T10:30:00']];

        $this->assertNotSame(
            FlightMatch::key(['cabin' => 'Economy', 'segments' => $segments]),
            FlightMatch::key(['cabin' => 'Business', 'segments' => $segments]),
        );
    }

    public function test_every_leg_counts_including_the_return_and_multi_city(): void
    {
        $out = ['airlineCode' => 'EK', 'flightNo' => 'EK784', 'departDT' => '2026-10-20T14:10:00'];

        $oneWay = ['cabin' => 'Economy', 'segments' => [$out]];
        $return = $oneWay + ['returnSegments' => [['airlineCode' => 'EK', 'flightNo' => 'EK783', 'departDT' => '2026-11-03T04:20:00']]];
        $this->assertNotSame(FlightMatch::key($oneWay), FlightMatch::key($return));

        // Multi-city flights carry no top-level segments; two different
        // itineraries must not collapse into one key.
        $multiA = ['cabin' => 'Economy', 'multiLegs' => [['segments' => [$out]]]];
        $multiB = ['cabin' => 'Economy', 'multiLegs' => [['segments' => [['airlineCode' => 'TK', 'flightNo' => 'TK1980', 'departDT' => '2026-10-27T07:00:00']]]]];
        $this->assertNotSame(FlightMatch::key($multiA), FlightMatch::key($multiB));
    }

    public function test_tagging_keeps_an_existing_key(): void
    {
        $tagged = FlightMatch::tag([['matchKey' => 'kept', 'segments' => []]]);

        $this->assertSame('kept', $tagged[0]['matchKey']);
    }
}
