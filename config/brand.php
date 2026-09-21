<?php

/*
 * One source of truth for the things every outgoing email prints.
 *
 * Before this, 26 email templates each carried their own copy: three
 * different brand blues (#0D1883 in 30 places, #303191 in 14), a green that
 * was #009933 rather than the site's #00a859, and seven templates still
 * pointing at "https://your-travelwheel-logo-url.png" — a placeholder that
 * had been shipping to customers as a broken image.
 */

return [
    'name' => env('BRAND_NAME', 'TravelWheel'),
    'tagline' => env('BRAND_TAGLINE', 'Simplifying access to travel.'),

    'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@travelwheel.ng'),
    'support_phone' => env('BRAND_SUPPORT_PHONE', '+234 805 626 5618'),
    'support_whatsapp' => env('BRAND_SUPPORT_WHATSAPP', '+234 803 270 5319'),
    'address' => env('BRAND_ADDRESS', '74 Ayangburen Road, Ikorodu, Lagos, Nigeria'),

    // Matches public/css/travelwheel-ui.css — see --tw-brand / --tw-accent.
    'colors' => [
        'brand' => '#303191',
        'brand_dark' => '#24246e',
        'accent' => '#00a859',
        'ink' => '#111827',
        'text' => '#1f2937',
        'muted' => '#667085',
        'subtle' => '#98a2b3',
        'line' => '#e6e8ee',
        'line_soft' => '#eef0f4',
        'surface' => '#f4f5f9',
        'panel' => '#fafbfd',
        'danger' => '#b42318',
        'danger_bg' => '#fef3f2',
        'warning' => '#92400e',
        'warning_bg' => '#fffaf0',
        'success' => '#04713f',
        'success_bg' => '#e9f9f0',
    ],
];
