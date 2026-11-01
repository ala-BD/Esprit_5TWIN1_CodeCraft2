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
        'image_model' => env('GEMINI_IMAGE_MODEL', 'gemini-3.1-flash-image'),
        'timeout' => env('GEMINI_TIMEOUT', 45),
    ],

    'donation_images' => [
        'provider' => env('DONATION_IMAGE_PROVIDER', 'comfyui'),
        'comfyui' => [
            'url' => env('COMFYUI_URL', 'http://127.0.0.1:8188'),
            'checkpoint' => env('COMFYUI_CHECKPOINT'),
            'steps' => env('COMFYUI_STEPS', 20),
            'cfg' => env('COMFYUI_CFG', 6.5),
            'denoise' => env('COMFYUI_DENOISE', 0.5),
            'timeout' => env('COMFYUI_TIMEOUT', 180),
        ],
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
