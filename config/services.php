<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'travelnext' => [
        'base_url' => env('TRAVELNEXT_BASE_URL', 'https://travelnext.works/api/aeroVE5/'),
        'user_id' => env('TRAVELNEXT_USER_ID'),
        'password' => env('TRAVELNEXT_PASSWORD'),
        'access' => env('TRAVELNEXT_ACCESS'),
        'ip' => env('TRAVELNEXT_IP'),
    ],

    'skylink' => [
        'base_url' => env('SKYLINK_BASE_URL', 'https://247travels.com/api/'),
        'email' => env('SKYLINK_EMAIL'),
        'password' => env('SKYLINK_PASSWORD'),
        // Kill switch for the live search-results merge (see FlightPage::
        // loadSkylinkResults()) — same convention as VISA_PRODUCT_ENABLED
        // (config/visa.php). Defaults OFF so deploying this code changes
        // nothing for real customers until explicitly turned on, and turning
        // it back off is instant — no redeploy, just an env change (plus
        // `php artisan config:clear` if the target env caches config).
        'enabled' => env('SKYLINK_ENABLED', false),

        // Per-call timeouts, in seconds. Deliberately different per endpoint
        // because the cost of cutting one short is not the same:
        //
        //  search  — user-facing, fired from wire:init while the results page
        //            is already painted. Live data over one week: 57 calls,
        //            avg 7.2s, and a bimodal tail — 5 calls over 30s (worst
        //            35.2s) and nothing at all between 20s and 30s. So a 20s
        //            cap discards exactly the stuck calls and no healthy ones,
        //            and keeps the whole request well inside a typical 30s
        //            max_execution_time / proxy limit instead of letting the
        //            web server kill it with a 500.
        //  pricing — checkout, a deliberate user action; worth waiting longer.
        //  reserve — payment has ALREADY been captured and this creates a live
        //            PNR. Timing out here means we don't know whether a ticket
        //            was issued, so it gets the most room, never less.
        'search_timeout' => (int) env('SKYLINK_SEARCH_TIMEOUT', 20),
        'pricing_timeout' => (int) env('SKYLINK_PRICING_TIMEOUT', 30),
        'reserve_timeout' => (int) env('SKYLINK_RESERVE_TIMEOUT', 90),
        // A credential POST that should answer in well under a second. Kept
        // deliberately tight because on a cold token cache it is spent BEFORE
        // the search, and the two together have to clear the proxy limit: the
        // 35.2s call above ran to completion and logged, so PHP's own
        // max_execution_time was never the thing killing those requests — the
        // web server in front of it was. 6 + 20 leaves real headroom under a
        // 30s gateway timeout, and skylink:warm-token means the 6 is almost
        // never actually spent.
        'auth_timeout' => (int) env('SKYLINK_AUTH_TIMEOUT', 6),

        // SkyLink offers stay valid 10-15 minutes (their docs), so re-serving
        // an identical search for a few minutes turns a reload, a back-button,
        // or a repeated search from a ~7s wait into an instant one. Safe for
        // booking because the cached booking_token is at most this old, still
        // inside SkyLink's own validity window, and select() re-prices through
        // /flights/pricing for a fresh token before anything is reserved.
        // Prices are not customer-specific, so the entry is shared; markup is
        // applied after the cache, so pricing config changes apply at once.
        // Set to 0 to disable.
        'search_cache_ttl' => (int) env('SKYLINK_SEARCH_CACHE_TTL', 180),
    ],

    'seerbit' => [
        'base_url' => env('SEERBIT_BASE_URL', 'https://seerbitapi.com'),
        'public_key' => env('SEERBIT_PUBLIC_KEY'),
        'secret_key' => env('SEERBIT_SECRET_KEY'),
        'country' => env('SEERBIT_COUNTRY', 'NG'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'budpay' => [
        'public_key' => env('BUDPAY_PUBLIC_KEY'),
        'secret_key' => env('BUDPAY_SECRET_KEY'),
    ],

    'visa' => [
        'quote_ttl_minutes' => env('VISA_QUOTE_TTL_MINUTES', 30),
    ],

    'sanlam' => [
        'base_url' => env('SANLAM_BASE_URL', 'https://web-app.sanlamallianz.com.ng'),
        'username' => env('SANLAM_USERNAME'),
        'password' => env('SANLAM_PASSWORD'),
    ],

    'loungepair' => [
        'base_url' => env('LOUNGEPAIR_BASE_URL', 'https://www.loungepair.com'),
        'client_id' => env('LOUNGEPAIR_CLIENT_ID') ?: env('LP_CLIENT_ID'),
        'client_secret' => env('LOUNGEPAIR_CLIENT_SECRET') ?: env('LP_CLIENT_SECRET'),
        'currency' => env('LOUNGEPAIR_CURRENCY') ?: env('LP_CURRENCY'),
        'airport_path' => env('LOUNGEPAIR_AIRPORT_PATH', '/api/v1/at'),
    ],

];
