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
    'whatsapp' => [
        'token' => env('META_WHATSAPP_TOKEN'),
        'phone_number_id' => env('META_WHATSAPP_PHONE_ID'),
        'business_account_id' => env('META_WHATSAPP_BUSINESS_ID'),
        'app_secret' => env('META_WHATSAPP_APP_SECRET'),
        'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN', 'lidiag_whatsapp_2026'),
    ],

    'transcriptor' => [
        'url' => env('TRANSCRIPTOR_SERVICE_URL', 'http://localhost:8001'),
        'timeout' => env('TRANSCRIPTOR_TIMEOUT', 300),
    ],

    'teleradiologia' => [
        'hmac_secret' => env('TELERADIOLOGIA_HMAC_SECRET'),
        'orden_url' => env('TELERADIOLOGIA_ORDEN_URL', 'https://ris.tlimagen.com/RISServices/integracion/salud_tolima/ordenes'),
        'connect_timeout' => env('TELERADIOLOGIA_CONNECT_TIMEOUT', 30),
        'timeout' => env('TELERADIOLOGIA_TIMEOUT', 60),
        'retries' => env('TELERADIOLOGIA_RETRIES', 1),
    ],

];
