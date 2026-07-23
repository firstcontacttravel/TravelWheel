<?php

return [
    'fresh_for_minutes' => 5,

    'products' => [
        'flights' => ['label' => 'Flights', 'color' => '#2563eb'],
        'travel_flex' => ['label' => 'TravelFlex', 'color' => '#7c3aed'],
        'visa' => ['label' => 'Visa', 'color' => '#0891b2'],
        'air_cargo' => ['label' => 'Air Cargo', 'color' => '#d97706'],
        'car_hire' => ['label' => 'Car Hire', 'color' => '#059669'],
        'transfer' => ['label' => 'Transfers', 'color' => '#0d9488'],
        'lounge' => ['label' => 'Lounge', 'color' => '#db2777'],
        'protocol' => ['label' => 'Protocol', 'color' => '#9333ea'],
        'insurance' => ['label' => 'Insurance', 'color' => '#4f46e5'],
        'support' => ['label' => 'Support Services', 'color' => '#475569'],
    ],

    'metrics' => [
        'gross_value' => [
            'label' => 'Gross booking value',
            'definition' => 'Total customer value created in the selected period, excluding TravelFlex duplication.',
        ],
        'verified_collections' => [
            'label' => 'Verified collections',
            'definition' => 'Payments whose product payment record is in a verified or successful state.',
        ],
        'travelwheel_revenue' => [
            'label' => 'TravelWheel revenue',
            'definition' => 'Service charges, markups, and TravelWheel-payee quote items attributable to TravelWheel.',
        ],
        'supplier_cost' => [
            'label' => 'Supplier cost',
            'definition' => 'Known supplier or authority cost. Products without a trusted cost source remain unknown.',
        ],
        'gross_profit' => [
            'label' => 'Gross profit',
            'definition' => 'Gross booking value less known supplier or authority cost and tax, only where the source is reliable.',
        ],
        'orders' => [
            'label' => 'Transactions',
            'definition' => 'Distinct normalized product records in the selected period.',
        ],
        'conversion_rate' => [
            'label' => 'Payment conversion',
            'definition' => 'Paid product records divided by all product records in the selected period.',
        ],
    ],
];
