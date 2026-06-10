<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GPS accuracy limits (self-service check-in / check-out)
    |--------------------------------------------------------------------------
    |
    | Reject readings worse than max_accuracy_m. Warn in UI above warn_accuracy_m.
    |
    */
    'max_accuracy_m' => (float) env('ATTENDANCE_MAX_GPS_ACCURACY_M', 150),

    'warn_accuracy_m' => (float) env('ATTENDANCE_WARN_GPS_ACCURACY_M', 50),

    /*
    | Maximum age (seconds) of a client-captured GPS fix allowed on submit.
    */
    'max_capture_age_seconds' => (int) env('ATTENDANCE_MAX_CAPTURE_AGE_SECONDS', 120),

];
