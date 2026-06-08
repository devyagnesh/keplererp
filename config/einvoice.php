<?php

return [

    /*
    |--------------------------------------------------------------------------
    | e-Invoice integration driver
    |--------------------------------------------------------------------------
    |
    | log — stub IRN for dev/demo (no external API).
    | nic — live IRP via GSP/NIC REST API (configure .env NIC_* keys below).
    |
    */
    'driver' => env('EINVOICE_DRIVER', 'log'),

    'nic' => [
        'base_url' => env('EINVOICE_NIC_BASE_URL'),
        'auth_path' => env('EINVOICE_NIC_AUTH_PATH', '/authenticate'),
        'generate_path' => env('EINVOICE_NIC_GENERATE_PATH', '/einvoice/generate'),
        'username' => env('EINVOICE_NIC_USERNAME'),
        'password' => env('EINVOICE_NIC_PASSWORD'),
        'client_id' => env('EINVOICE_NIC_CLIENT_ID'),
        'client_secret' => env('EINVOICE_NIC_CLIENT_SECRET'),
        'gstin' => env('EINVOICE_NIC_GSTIN'),
        'timeout_seconds' => (int) env('EINVOICE_NIC_TIMEOUT', 30),
        'token_ttl_seconds' => (int) env('EINVOICE_NIC_TOKEN_TTL', 3300),
    ],
];
