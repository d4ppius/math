<?php

/*
|--------------------------------------------------------------------------
| Legal details (Impressum, privacy statement)
|--------------------------------------------------------------------------
|
| Read from the .env so that no personal or company details live in the
| public repository. Read through config() (never env() directly) so the
| values keep working with `php artisan optimize` / config:cache.
|
*/

return [
    // Required for the Impressum: shown as a visible hint on the page if missing.
    'name' => env('LEGAL_NAME'),
    'street' => env('LEGAL_STREET'),
    'zip_city' => env('LEGAL_ZIP_CITY'),
    'email' => env('LEGAL_EMAIL'),

    'country' => env('LEGAL_COUNTRY', 'Schweiz'),

    // Optional.
    'responsible' => env('LEGAL_RESPONSIBLE'),
    'phone' => env('LEGAL_PHONE'),
    'uid' => env('LEGAL_UID'),

    // Named in the privacy statement, e.g. "Hostpoint AG, Schweiz".
    'hoster' => env('LEGAL_HOSTER'),
];
