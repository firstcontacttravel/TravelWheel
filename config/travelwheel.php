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
        \App\Mail\ETicketMail::class => null,                 // ticketed straight after payment (LCC, SkyLink, TravelFlex) — these send no receipt
        \App\Mail\TravelFlexTicketBookedMail::class => null,  // TravelFlex ticket issued
        // Car Hire & Pick up 'n' Drop off (internal notification copy)
        \App\Mail\CarHireNotificationMail::class => null,
        \App\Mail\TransferNotificationMail::class => null,
        \App\Mail\DriverAssignedMail::class => null,          // driver assigned (the customer's copy)
        // Lounge: LoungeReservationNotificationMail (the full booking form) is
        // sent straight to the reservations inbox by LoungeController, so the
        // customer's thin confirmation isn't copied as well.
        // Protocol, Air Cargo (customer confirmation — no internal copy exists)
        \App\Mail\ProtocolBookingMail::class => null,
        \App\Mail\ShipmentMail::class => null,
        // Support (internal notification copy)
        \App\Mail\SupportFlightAssistNotificationMail::class => null,
        \App\Mail\SupportExtraLuggageNotificationMail::class => null,
        \App\Mail\SupportVisaConfirmationNotificationMail::class => null,
        \App\Mail\SupportYellowCardNotificationMail::class => null,
        // Insurance: InsurancePurchaseNotificationMail is sent straight to the
        // reservations inbox by InsuranceController, so it isn't listed here.
        // Visa — only the "application submitted" update, not every status change
        \App\Mail\VisaApplicationUpdateMail::class => 'Visa application submitted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thank-you email after every booking
    |--------------------------------------------------------------------------
    | After a customer's booking confirmation goes out, App\Listeners\
    | SendBookingThankYou sends them a thank-you from the reservations inbox
    | that suggests our other products. "thank_you_triggers" names the
    | customer confirmation email per product (a [product, subject prefix]
    | pair when the mail class sends many kinds of update); "products" is
    | what the email suggests, in this order. Insurance sends the customer
    | no confirmation, so InsuranceController sends its thank-you directly.
    */
    'thank_you_triggers' => [
        \App\Mail\PaymentReceiptMail::class => 'flights',            // paid flight booking
        \App\Mail\ETicketMail::class => 'flights',                   // ticketed straight after payment
        \App\Mail\TravelFlexTicketBookedMail::class => 'flights',
        \App\Mail\LoungeBookingMail::class => 'lounge',
        \App\Mail\ProtocolBookingMail::class => 'protocol',
        \App\Mail\ShipmentMail::class => 'air_cargo',
        \App\Mail\CarHireSuccessMail::class => 'travel_connections',
        \App\Mail\TransferSuccessMail::class => 'travel_connections',
        \App\Mail\SupportFlightAssistSuccessMail::class => 'travel_assist',
        \App\Mail\SupportExtraLuggageSuccessMail::class => 'travel_assist',
        \App\Mail\SupportVisaConfirmationSuccessMail::class => 'travel_assist',
        \App\Mail\SupportYellowCardSuccessMail::class => 'travel_assist',
        \App\Mail\VisaApplicationUpdateMail::class => ['visa', 'Visa application submitted'],
    ],
    'products' => [
        'flights' => ['name' => 'Flights', 'pitch' => 'Book local and international flights at great fares, with flexible payment through TravelFlex.', 'url' => '/', 'icon' => 'assets/Flight 70.png'],
        'protocol' => ['name' => 'Airport Protocol', 'pitch' => 'Meet & greet, fast-track and escort through the airport.', 'route' => 'air.protocol', 'icon' => 'assets/Protocol 70.png'],
        'lounge' => ['name' => 'Airport Lounge', 'pitch' => 'Relax before your flight in comfortable airport lounges.', 'route' => 'air.lounge', 'icon' => 'assets/Lounge 70.png'],
        'insurance' => ['name' => 'Travel Insurance', 'pitch' => 'Travel with peace of mind: medical, baggage and trip cover.', 'route' => 'air.insurance', 'icon' => 'assets/Insurance 70.png'],
        'visa' => ['name' => 'Visa Assistance', 'pitch' => 'Expert help with your visa application, from documents to appointment.', 'route' => 'air.visa', 'icon' => 'assets/Visa 70.png'],
        'air_cargo' => ['name' => 'Air Cargo', 'pitch' => 'Send documents and parcels locally and abroad.', 'route' => 'air.cargo', 'icon' => 'assets/Air Cargo 70.png'],
        'travel_connections' => ['name' => 'Car Hire & Pick up \'n\' Drop off', 'pitch' => 'Chauffeur-driven cars by the hour, and rides between airports, cities and towns.', 'route' => 'air.carhire', 'icon' => 'assets/Car Hire 70.png'],
        'travel_assist' => ['name' => 'Travel Assist', 'pitch' => 'Yellow Card, extra luggage, flight assist and visa confirmation.', 'route' => 'air.support', 'icon' => 'assets/Support 70.png'],
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
