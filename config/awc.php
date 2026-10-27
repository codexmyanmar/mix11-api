<?php
// config/awc.php

return [
    // API Base URL - Try tttint first (from curl example)
    'api_url' => env('AWC_API_URL', 'https://tttint.apihub55.com/'),
    
    // Security Credentials
    'secret_key' => env('AWC_SECRET_KEY', 'Ezq8XtyQq2zlmdCtZcp'),
    'agent_id' => env('AWC_AGENT_ID', 'pg88staging'),
    
    // Callback Configuration
    'callback_url' => env('AWC_CALLBACK_URL', 'https://zvz-commerce.com/api/awc'),
    
    // Default Settings
    'default_currency' => env('AWC_DEFAULT_CURRENCY', 'THB'),
    'default_language' => env('AWC_DEFAULT_LANGUAGE', 'en'),
    
    // HTTP Method (POST or GET)
    'use_get_method' => env('AWC_USE_GET_METHOD', false),
    
    // Game Settings
    'supported_providers' => [
        'rich88', 'cq9', 'live22', 'awc', 'jili', 'pp', 'pg', 'fc', 'acewin', 'jdb'
    ],
    
    // Bet Limit Configuration
    'bet_limits' => [
        'SEXYBCRT' => [
            'LIVE' => [
                'limitId' => [280301, 280303, 280307]
            ]
        ]
    ],
    
    // Jackpot Settings
    'jackpot_enabled' => env('AWC_JACKPOT_ENABLED', false),
    
    // Timeout Settings
    'timeout' => 30,
    'retry_times' => 3,
    'retry_milliseconds' => 100,
];