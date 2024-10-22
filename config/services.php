<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'api' => [
        'user' => env('API_USER'),
        'secret' => env('API_SECRET'),
        'endpoint' => env('API_ENDPOINT'),
    ],

    'passport' => [
        'client_id' => env('PASSPORT_CLIENT_ID'),
        'client_secret' => env('PASSPORT_CLIENT_SECRET'),
        'token_endpoint' => env('PASSPORT_TOKEN_ENDPOINT'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.eu.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'digital-ocean' => [
        'serverless-functions' => [
            'quickchart' => [
                'url' => env('QUICKCHART_FUNCTION_URL', 'https://faas-fra1-afec6ce7.doserverless.co/api/v1/namespaces/fn-0bc28cb8-f671-491a-a17d-6d724af0f3fc/actions/votes365.org/quickchart?blocking=true&result=true'),
                'auth' => env('QUICKCHART_FUNCTION_AUTH', ''),
            ],
        ],
    ],

    'google' => [
        'static-map' => [
            'url' => 'https://maps.googleapis.com/maps/api/staticmap',
            'api-key' => env('GOOGLE_MAP_KEY', ''),
        ],
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
