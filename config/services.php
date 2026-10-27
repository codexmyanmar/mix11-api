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
    
    'kingkong' => [
        'secret_key' => env('KINGKONG_SECRET_KEY'),
    ],
    
    
    'buffalo' => [
        'base_url' => env('BUFFALO_API_URL', 'https://api-ms3.african-buffalo.club'),
        'domain' => env('BUFFALO_DOMAIN', 'PG8688.com'),
        'secret_key' => env('BUFFALO_SECRET_KEY', 'your-secret-key-here'),
        'conversion_rate' => env('BUFFALO_CONVERSION_RATE', 100),
    ],
    
     'rich88' => [
        'server_url' => env('RICH88_SERVER_URL', 'https://lobbycenter.ark8899.com'),
        'pf_id' => env('RICH88_PF_ID', 'acthb_IPG8'),
        'private_key' => env('RICH88_PRIVATE_KEY', '3hBYDqOn8ugKZa88AwTd7JVB5igt7kE5'),
        'lobby_url' => env('RICH88_LOBBY_URL', 'https://lobby.r88-gaming.com'),
        'back_office_url' => env('RICH88_BACK_OFFICE', 'https://lobby-cms.r88gamer.com'),
         'currency' => env('RICH88_CURRENCY', 'THB2'),
    ],
    
    'bole' => [
        'api_domain' => env('BOLE_API_DOMAIN', 'https://api2.bole-game.com'),
        'access_key_id' => env('BOLE_ACCESS_KEY_ID'),
        'access_key_secret' => env('BOLE_ACCESS_KEY_SECRET'),
        'wallet_key' => env('BOLE_WALLET_KEY'),
         'wallet_key' => env('BOLE_WALLET_KEY', 'pg_boss_wallet_key'),
        'agent_id' => env('BOLE_AGENT_ID', 'pg@admin.com'),
        'country' => env('BOLE_COUNTRY', 'TH'),
        'lang' => env('BOLE_LANG', 'en_US'),
        'currency' => env('BOLE_CURRENCY', 'THB'),  // Changed to THB
        'operator_id' => 2580,
        'app_id' => 'test',
    ],

];
