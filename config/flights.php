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

    /*
    |--------------------------------------------------------------------------
    | Parallel search
    |--------------------------------------------------------------------------
    |
    | On: Search opens the results page at once and every switched-on API is
    | searched side by side, each in its own request, with flights appearing
    | as each answers. Off: the loading page tries the APIs one after another
    | in priority order, and the rest load once the results page is up.
    |
    | Each search remembers which way it ran, so switching this never breaks
    | a results page a customer already has open. Before turning it on, check
    | the host allows enough PHP workers: every search holds one per
    | switched-on API for as long as that API takes to answer.
    |
    */

    'parallel_search' => (bool) env('FLIGHTS_PARALLEL_SEARCH', false),

    // How long a search can be reopened (reload, back button) before it has
    // to be run again, and how long one API's answer is reused before a
    // reload asks that API afresh. Fares go stale well within the latter.
    'search_minutes' => (int) env('FLIGHTS_SEARCH_MINUTES', 120),
    'result_minutes' => (int) env('FLIGHTS_RESULT_MINUTES', 20),
];
