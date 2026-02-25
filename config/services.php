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
'kcb_buni' => [
        'base_url' => env('KCB_BUNI_BASE_URL', 'https://api.buni.kcbgroup.com'),
        'consumer_key' => env('KCB_BUNI_CONSUMER_KEY'),
        'consumer_secret' => env('KCB_BUNI_CONSUMER_SECRET'),
        'environment' => env('KCB_BUNI_ENVIRONMENT', 'sandbox'),
        'till_number' => env('KCB_TILL_NUMBER'),
        'callback_base_url' => env('APP_URL'),
    ],

    'advanta' => [
        'base_url' => env('ADVANTA_BASE_URL', 'https://quicksms.advantasms.com'),
        'partner_id' => env('ADVANTA_PARTNER_ID'),
        'api_key' => env('ADVANTA_API_KEY'),
        'shortcode' => env('ADVANTA_SHORTCODE', 'QuickOffice'),
    ],
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],
         'advanta' => [
        'base_url' => env('ADVANTA_BASE_URL', 'https://quicksms.advantasms.com'),
        'partner_id' => env('ADVANTA_PARTNER_ID'),
        'api_key' => env('ADVANTA_API_KEY'),
        'shortcode' => env('ADVANTA_SHORTCODE', 'QuickOffice'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
