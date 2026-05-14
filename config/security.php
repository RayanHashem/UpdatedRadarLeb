<?php

return [
    'admin_two_factor' => [
        'enabled' => (bool) env('ADMIN_TWO_FACTOR_ENABLED', false),
        'code' => env('ADMIN_TWO_FACTOR_CODE'),
        'remember_minutes' => (int) env('ADMIN_TWO_FACTOR_REMEMBER_MINUTES', 480),
    ],

    'scan' => [
        'cooldown_seconds' => (int) env('SCAN_COOLDOWN_SECONDS', 4),
        // Keep nonce/location requirements false on HTTP staging. If either
        // gate rejects a request, AttemptScan never runs, so no wallet debit
        // is recorded. Re-enable after HTTPS/session behavior is verified.
        'nonce_required' => false,
        'location_required' => false,
        'location_max_age_seconds' => (int) env('SCAN_LOCATION_MAX_AGE_SECONDS', 30),
        'location_max_accuracy_meters' => (int) env('SCAN_LOCATION_MAX_ACCURACY_METERS', 100),
    ],
];
