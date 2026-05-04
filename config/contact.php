<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public support phone
    |--------------------------------------------------------------------------
    |
    | Phone number rendered in the dashboard help overlay and shared across
    | the Vue pages that previously hardcoded "71484833" inline. To change it
    | once for the whole app, update RADARLEB_SUPPORT_PHONE in your .env or
    | the default below.
    |
    */

    'support_phone' => env('RADARLEB_SUPPORT_PHONE', '71484833'),

    /*
    |--------------------------------------------------------------------------
    | Locale defaults
    |--------------------------------------------------------------------------
    |
    | Locales we explicitly support in the public Inertia app. The locale
    | switcher renders one option per entry. Each label is shown in its own
    | language so an Arabic-only speaker can identify it.
    |
    */

    'locales' => [
        'en' => 'English',
        'ar' => 'العربية',
    ],
];
