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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // IA M3 Upcycling (Google Gemini) — sans clé, un générateur local prend le relais
    'gemini' => [
        'key'     => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        'timeout' => env('GEMINI_TIMEOUT', 45),
    ],

    // IA M4 Recyclage (micro-service Python ai-recyclage) — s'il est arrêté, les règles métier prennent le relais
    'ai_recyclage' => [
        'url'     => env('AI_RECYCLAGE_URL', 'http://127.0.0.1:8001'),
        'timeout' => env('AI_RECYCLAGE_TIMEOUT', 5),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],

];
