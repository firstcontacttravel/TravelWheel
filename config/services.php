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
