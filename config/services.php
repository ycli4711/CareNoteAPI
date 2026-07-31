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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'wechat_mini_program' => [
        'app_id' => env('WECHAT_MINI_PROGRAM_APP_ID'),
        'app_secret' => env('WECHAT_MINI_PROGRAM_APP_SECRET'),
        'access_token_ttl_seconds' => (int) env('WECHAT_MINI_PROGRAM_ACCESS_TOKEN_TTL_SECONDS', 86400),
        'refresh_token_ttl_seconds' => (int) env('WECHAT_MINI_PROGRAM_REFRESH_TOKEN_TTL_SECONDS', 2592000),
    ],

];
