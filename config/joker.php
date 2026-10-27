<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Joker Game API Configuration
    |--------------------------------------------------------------------------
    */
    
    'api_url' => env('JOKER_API_URL', 'https://w.apiext88.net/seamless'),
    'forward_url' => env('JOKER_FORWARD_URL', 'https://www.weimen99f.net'),
    'app_id' => env('JOKER_APP_ID', 'TLK1'),
    'secret_key' => env('JOKER_SECRET_KEY', ''),
    
    'agent_backend' => env('JOKER_AGENT_BACKEND'),
    'agent_username' => env('JOKER_AGENT_USERNAME'),
    'agent_password' => env('JOKER_AGENT_PASSWORD'),
    
    'currency' => env('JOKER_CURRENCY', 'THB'),
    'timeout' => env('JOKER_TIMEOUT', 10),
    'retry_count' => env('JOKER_RETRY_COUNT', 3),
    
    /*
    |--------------------------------------------------------------------------
    | Game Point Conversion
    | THB: 1 Real THB = 1 BO = 100 Game Points
    |--------------------------------------------------------------------------
    */
    'point_conversion' => [
        'THB' => 100,  // 1 real = 100 game points
        'MMK' => 1,    // 1 real = 1 game point
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    */
    'decimal_precision' => 2,
    
    /*
    |--------------------------------------------------------------------------
    | Request Timeout in Seconds (API must respond within 2.5 seconds)
    |--------------------------------------------------------------------------
    */
    'max_response_time' => 2.5,
    
    /*
    |--------------------------------------------------------------------------
    | Timestamp Validation (minutes)
    | For sensitive APIs like bet, withdraw, join tournament
    |--------------------------------------------------------------------------
    */
    'timestamp_tolerance_sensitive' => 10,
    'timestamp_tolerance_retry' => 1440, // 24 hours for retry APIs
];