<?php

/*
 * Corporate accounts: companies that book flights through /business.
 * See docs/corporate-flights-plan.md.
 */

return [
    // Who hears about a new request from the public form.
    'notify_email' => env('CORPORATE_EMAIL', env('RESERVATIONS_EMAIL', 'reservation@travelwheel.ng')),

    // Shows Corporate links on the public site. Off until the pilot is done.
    'public_links' => (bool) env('CORPORATE_PUBLIC_LINKS', false),

    // What we promise on SkyLink tickets and changes, shown in those booking
    // emails. Nothing is shown until it's set, for example "within 4 working hours".
    'skylink_turnaround' => env('CORPORATE_SKYLINK_TURNAROUND'),

    'invitation_days' => 7,

    // Saved travellers of a company suspended this long are deleted, if nobody
    // has deleted them by hand. See corporate:housekeeping.
    'traveller_retention_days' => 30,

    'invoice' => [
        // Printed on invoices once the accountant confirms it.
        'tax_id' => env('CORPORATE_TAX_ID'),
    ],

    'login_code' => [
        'minutes' => 10,
        'max_attempts' => 5,
        'resend_after_seconds' => 60,
    ],
];
