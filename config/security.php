<?php

return [
    'admin_two_factor' => [
        'enabled' => (bool) env('ADMIN_TWO_FACTOR_ENABLED', false),
        'code' => env('ADMIN_TWO_FACTOR_CODE'),
        'remember_minutes' => (int) env('ADMIN_TWO_FACTOR_REMEMBER_MINUTES', 480),
    ],

    'scan' => [
        'cooldown_seconds' => (int) env('SCAN_COOLDOWN_SECONDS', 4),
        'nonce_required' => (bool) env('SCAN_NONCE_REQUIRED', false),
        'location_required' => (bool) env('SCAN_LOCATION_REQUIRED', false),
        'location_max_age_seconds' => (int) env('SCAN_LOCATION_MAX_AGE_SECONDS', 30),
        'location_max_accuracy_meters' => (int) env('SCAN_LOCATION_MAX_ACCURACY_METERS', 100),
    ],
];
