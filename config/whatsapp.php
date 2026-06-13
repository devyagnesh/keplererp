<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp integration driver
    |--------------------------------------------------------------------------
    |
    | "cloud" — Meta WhatsApp Cloud API (SRS §17).
    | "log"   — No HTTP; writes laravel.log + whatsapp_logs as SENT (testing / local).
    |
    */
    'driver' => env('WHATSAPP_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API (WABA)
    |--------------------------------------------------------------------------
    |
    | Create templates in Meta Business Suite; names must match template_name
    | sent by the app (defaults below). SRS §17.1: WABA_PHONE_NUMBER_ID,
    | WABA_ACCESS_TOKEN in .env.
    |
    | Endpoint (same as curl): POST https://graph.facebook.com/{graph_version}/{phone_number_id}/messages
    |
    */
    'cloud' => [
        'phone_number_id' => env('WABA_PHONE_NUMBER_ID'),
        'access_token' => env('WABA_ACCESS_TOKEN'),
        'graph_version' => env('WABA_GRAPH_VERSION', 'v25.0'),
    ],

    'default_locale' => env('WHATSAPP_TEMPLATE_LOCALE', 'en'),

    /*
    | Default country calling code when normalising 10-digit Indian mobiles.
    */
    'default_country_calling_code' => env('WHATSAPP_DEFAULT_CC', '91'),

    /*
    |--------------------------------------------------------------------------
    | Template names (Meta-approved names must match)
    |--------------------------------------------------------------------------
    |
    | Body parameters are sent in order as TEXT components for the template
    | BODY. Adjust counts to match each template in Meta Business Suite.
    |
    */
    'templates' => [
        'po_approved' => 'po_approved',
        'po_dispatch' => 'po_dispatch',
        'po_staff_update' => 'po_staff_update',
        'grn_posted' => 'grn_posted',
        'invoice_sent' => 'invoice_sent',
        'low_stock' => 'low_stock',
        'pr_rejected' => 'pr_rejected',
        'pr_approved' => 'pr_approved',
        'payment_receipt' => 'payment_receipt',
        'payment_sent' => 'payment_sent',
        'payment_overdue' => 'payment_overdue',
        'leave_approved' => 'leave_approved',
        'license_expiry' => 'license_expiry',
        'prod_started' => 'prod_started',
        'prod_complete' => 'prod_complete',
        'salary_credited' => 'salary_credited',
        'vendor_portal' => 'vendor_portal',
        'po_vendor_accepted' => 'po_vendor_accepted',
        'quotation_sent' => 'quotation_sent',
        'employee_portal' => 'employee_portal',
    ],
];
