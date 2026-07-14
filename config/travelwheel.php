<?php

return [
    'travelflex_interest_rate' => (float) env('TRAVELFLEX_INTEREST_RATE', 0.04),
    'travelflex_administration_fee_rate' => (float) env('TRAVELFLEX_ADMINISTRATION_FEE_RATE', 0.01),
    'travelflex_insurance_fee_rate' => (float) env('TRAVELFLEX_INSURANCE_FEE_RATE', 0.015),
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
