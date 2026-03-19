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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'prenota' => [
        'base' => env('PRENOTA_API_BASE'),
        'version' => env('PRENOTA_API_VERSION', '2'),
        'auth' => env('PRENOTA_API_AUTH'),
    ],
    'leadconnector' => [
        'webhook' => env('LEADCONNECTOR_WEBHOOK'),
        'slope_webhook' => env('LEADCONNECTOR_SLOPE_WEBHOOK', 'https://services.leadconnectorhq.com/hooks/KpPMJMX6X6M1yDnxEIWL/webhook-trigger/d5fced04-16d9-4f0d-b327-10c0dd7697ee'),
    ],
    'sync' => [
        'secret' => env('SYNC_SECRET'),
    ],

    'slope' => [
        'base_url' => env('SLOPE_BASE_URL') ?: (env('APP_ENV') === 'production' ? 'https://api.slope.it' : 'https://api.staging.slope.it'),
        'timeout' => env('SLOPE_TIMEOUT', 60),
        'retry_attempts' => env('SLOPE_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('SLOPE_RETRY_DELAY', 1),
        'sync_days_ahead' => env('SLOPE_SYNC_DAYS_AHEAD', 15), // Prossimi 15 giorni
        'enabled' => env('SLOPE_SYNC_ENABLED', true),
    ],

];
