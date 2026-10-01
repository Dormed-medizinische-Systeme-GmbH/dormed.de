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

    'cas_genesis_world' => [
        'host' => env('CAS_GENESIS_WORLD_HOST'),
        'username' => env('CAS_GENESIS_WORLD_USERNAME'),
        'password' => env('CAS_GENESIS_WORLD_PASSWORD'),
        'product_key' => env('CAS_GENESIS_WORLD_PRODUCT_KEY'),
        'webhook_bearer' => env('CAS_GENESIS_WORLD_WEBHOOK_BEARER'),
    ],

    'decision_model' => [
        'url' => env('DECISION_MODEL_URL'),
        'key' => env('DECISION_MODEL_KEY'),
    ],

];
