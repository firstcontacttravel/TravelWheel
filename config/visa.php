<?php

return [
    'enabled' => env('VISA_PRODUCT_ENABLED', true),
    'legacy_import_enabled' => env('VISA_LEGACY_IMPORT_ENABLED', false),
    'monitoring_window_days' => env('VISA_MONITORING_WINDOW_DAYS', 30),
];
