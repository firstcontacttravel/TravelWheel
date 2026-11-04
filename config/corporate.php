<?php

/*
 * Corporate accounts: companies that book flights through /business.
 * See docs/corporate-flights-plan.md.
 */

return [
    // Who hears about a new request from the public form.
    'notify_email' => env('CORPORATE_EMAIL', env('RESERVATIONS_EMAIL', 'reservation@travelwheel.ng')),

    'invitation_days' => 7,

    'login_code' => [
        'minutes' => 10,
        'max_attempts' => 5,
        'resend_after_seconds' => 60,
    ],
];
