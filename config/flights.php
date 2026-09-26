<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flight suppliers
    |--------------------------------------------------------------------------
    |
    | Every flight API the site can search or book through, keyed by the value
    | stored in flight_bookings.supplier. Adding an API means implementing
    | App\Contracts\FlightSupplier and registering it here. Credentials and
    | timeouts stay in config/services.php.
    |
    */

    'suppliers' => [
        'travelnext' => App\Services\TravelnextFlightService::class,
        'skylink' => App\Services\SkylinkFlightService::class,
    ],
];
