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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'activation_expire_minutes' => env('ACTIVATION_EXPIRE_MINUTES'),
    'from_email' => env('FROM_EMAIL'),
    'frontend_url' => env('FRONTEND_URL'),
    'frontend_cookie_domain' => env('FRONTEND_COOKIE_DOMAIN'),
    'frontend_cookie_secure' => env('FRONTEND_COOKIE_SECURE'),
    'frontend_domain' => env('FRONTEND_DOMAIN'),
    'frontend_protocol' => env('FRONTEND_PROTOCOL'),
    'frontend_port' => env('FRONTEND_PORT'),
    'corporate_email' => env('CORPORATE_EMAIL'),

];
