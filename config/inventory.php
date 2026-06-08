<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batch expiry alert window (days)
    |--------------------------------------------------------------------------
    |
    | Batches with expiry on or before today + this many days appear in the
    | expiry alerts tab on the batch/serial traceability dashboard.
    |
    */
    'expiry_warn_days' => (int) env('INVENTORY_EXPIRY_WARN_DAYS', 30),

];
