<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_OAUTH_REDIRECT_URI'),
    ],
    'outlook' => [
        'client_id' => env('OUTLOOK_OAUTH_CLIENT_ID'),
        'client_secret' => env('OUTLOOK_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('OUTLOOK_OAUTH_REDIRECT_URI'),
        'tenant' => env('OUTLOOK_OAUTH_TENANT'),
    ],
];
