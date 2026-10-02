<?php

return [
    'travelflex_interest_rate' => (float) env('TRAVELFLEX_INTEREST_RATE', 0.04),
    'travelflex_administration_fee_rate' => (float) env('TRAVELFLEX_ADMINISTRATION_FEE_RATE', 0.01),
    'travelflex_insurance_fee_rate' => (float) env('TRAVELFLEX_INSURANCE_FEE_RATE', 0.015),
    'travelflex_minimum_down_payment_percent' => (int) env('TRAVELFLEX_MINIMUM_DOWN_PAYMENT_PERCENT', 30),
    'travelflex_maximum_down_payment_percent' => (int) env('TRAVELFLEX_MAXIMUM_DOWN_PAYMENT_PERCENT', 90),
    'travelflex_down_payment_percentage_step' => (int) env('TRAVELFLEX_DOWN_PAYMENT_PERCENTAGE_STEP', 10),
    'travelflex_refund_processing_fee' => (float) env('TRAVELFLEX_REFUND_PROCESSING_FEE', 0),
    'travelflex_refund_risk_buffer_rate' => (float) env('TRAVELFLEX_REFUND_RISK_BUFFER_RATE', 0.05),
    'travelflex_refund_risk_buffer_fixed' => (float) env('TRAVELFLEX_REFUND_RISK_BUFFER_FIXED', 0),
    /*
    | Reservations inbox: gets a blind copy (BCC) of one "a booking was made"
    | email per booking, for every product. The list below names which email
    | that is per product; a string value means only emails whose subject
    | starts with it (for mail classes that send many kinds of update).
    | Copies are added by App\Listeners\CopyReservationEmails.
    */
    'reservations_email' => env('RESERVATIONS_EMAIL', 'reservation@travelwheel.ng'),
    'reservation_mailables' => [
        // Flights
        \App\Mail\BookingPendingMail::class => null,          // bank transfer, awaiting payment
        \App\Mail\PaymentReceiptMail::class => null,          // paid booking
        \App\Mail\TravelFlexTicketBookedMail::class => null,  // TravelFlex ticket issued
        // Car Hire & Pick up 'n' Drop off (internal notification copy)
        \App\Mail\CarHireNotificationMail::class => null,
        \App\Mail\TransferNotificationMail::class => null,
        // Lounge, Protocol, Air Cargo (customer confirmation — no internal copy exists)
        \App\Mail\LoungeBookingMail::class => null,
        \App\Mail\ProtocolBookingMail::class => null,
        \App\Mail\ShipmentMail::class => null,
        // Support (internal notification copy)
        \App\Mail\SupportFlightAssistNotificationMail::class => null,
        \App\Mail\SupportExtraLuggageNotificationMail::class => null,
        \App\Mail\SupportVisaConfirmationNotificationMail::class => null,
        \App\Mail\SupportYellowCardNotificationMail::class => null,
        // Visa — only the "application submitted" update, not every status change
        \App\Mail\VisaApplicationUpdateMail::class => 'Visa application submitted',
    ],

    'admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),
    'travelflex_bank_accounts' => array_values(array_filter([
        [
            'bank' => env('TRAVELFLEX_BANK_1_NAME'),
            'account_number' => env('TRAVELFLEX_BANK_1_NUMBER'),
            'account_name' => env('TRAVELFLEX_BANK_1_ACCOUNT_NAME'),
        ],
        [
            'bank' => env('TRAVELFLEX_BANK_2_NAME'),
            'account_number' => env('TRAVELFLEX_BANK_2_NUMBER'),
            'account_name' => env('TRAVELFLEX_BANK_2_ACCOUNT_NAME'),
        ],
        [
            'bank' => env('TRAVELFLEX_BANK_3_NAME'),
            'account_number' => env('TRAVELFLEX_BANK_3_NUMBER'),
            'account_name' => env('TRAVELFLEX_BANK_3_ACCOUNT_NAME'),
        ],
    ], static fn (array $account): bool => filled($account['bank'])
        && filled($account['account_number'])
        && filled($account['account_name']))),
];
