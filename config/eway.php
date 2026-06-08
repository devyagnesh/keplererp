<?php

return [

    /*
    |--------------------------------------------------------------------------
    | e-Way bill integration driver
    |--------------------------------------------------------------------------
    |
    | log — stub e-Way number for dev/demo.
    | nic — live e-Way via GSP/NIC REST API (configure .env EWAY_NIC_* keys).
    |
    */
    'driver' => env('EWAY_DRIVER', 'log'),

    'nic' => [
        'base_url' => env('EWAY_NIC_BASE_URL'),
        'auth_path' => env('EWAY_NIC_AUTH_PATH', '/authenticate'),
        'generate_path' => env('EWAY_NIC_GENERATE_PATH', '/ewaybill/generate'),
        'username' => env('EWAY_NIC_USERNAME'),
        'password' => env('EWAY_NIC_PASSWORD'),
        'client_id' => env('EWAY_NIC_CLIENT_ID'),
        'client_secret' => env('EWAY_NIC_CLIENT_SECRET'),
        'gstin' => env('EWAY_NIC_GSTIN'),
        'timeout_seconds' => (int) env('EWAY_NIC_TIMEOUT', 30),
        'token_ttl_seconds' => (int) env('EWAY_NIC_TOKEN_TTL', 3300),
    ],

    /** Match tolerance (INR) for 3-way vendor invoice vs payable. */
    'match_tolerance' => (float) env('EWAY_MATCH_TOLERANCE', '1.00'),
];
