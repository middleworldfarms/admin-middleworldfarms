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

    'wordpress' => [
        'api_key' => env('MWF_API_KEY'),
        'api_base' => env('MWF_API_BASE_URL'),
        'base_url' => env('WOOCOMMERCE_URL'),
    ],

    'woocommerce' => [
        'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),
        'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
        'base_url' => env('WOOCOMMERCE_URL'),
        'api_url' => env('WOOCOMMERCE_URL'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'wp_api' => [
        'url'    => env('WOOCOMMERCE_URL', 'https://middleworldfarms.org'),
        'key'    => env('WOOCOMMERCE_CONSUMER_KEY'),
        'secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
    ],
    'wc_api' => [
        'url'             => env('WOOCOMMERCE_URL', ''),
        'consumer_key'    => env('WOOCOMMERCE_CONSUMER_KEY', ''),
        'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET', ''),
        'integration_key' => env('SELF_SERVE_SHOP_INTEGRATION_KEY', ''),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'delivery' => [
        'depot_address' => env('DELIVERY_DEPOT_ADDRESS', 'Middle World Farms, Bradney Road, Washingborough, Lincoln, LN4 1AQ, UK'),
    ],
    
    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],
];
