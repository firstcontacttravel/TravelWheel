<?php

namespace Tests\Fixtures;

use App\Services\TravelnextFlightService;

/**
 * A stand-in for "a new API was just added to config/flights.php".
 */
class SecondTravelnextSupplier extends TravelnextFlightService
{
    public function key(): string
    {
        return 'second_travelnext';
    }

    public function label(): string
    {
        return 'Second TravelNext';
    }
}
