<?php

return [
    'wtoken' => env('CQ9_WTOKEN', 'pg8688.club'),
    'api_url' => env('CQ9_API_URL', 'https://api.cqgame.games'),
    'auth_token' => env('CQ9_AUTH_TOKEN', ''),
    'bo_url' => env('CQ9_BO_URL', 'https://bo.cqgame.games'),
    'timezone' => 'America/New_York',
    'default_lang' => env('CQ9_DEFAULT_LANG', 'en'),
    'default_platform' => env('CQ9_DEFAULT_PLATFORM', 'web'),
    'debug' => env('CQ9_DEBUG', true),
];