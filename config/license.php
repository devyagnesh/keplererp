<?php

return [

    /*
    |--------------------------------------------------------------------------
    | License AMC reminders (SRS §18)
    |--------------------------------------------------------------------------
    |
    | warn_days_before_expiry: send WhatsApp WA-14 when days until expiry <= this value.
    | renewal_url: shown as template variable {{2}} (contact vendor / renewal page).
    |
    */
    'warn_days_before_expiry' => (int) env('LICENSE_WARN_DAYS', 30),

    'renewal_url' => env('LICENSE_RENEWAL_URL'),

];
