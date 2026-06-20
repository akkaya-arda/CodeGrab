<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Guard Email Timeframe Limit
    |--------------------------------------------------------------------------
    |
    | Defines the maximum age (in seconds) allowed for intercepted guard code
    | emails. Emails older than this will be rejected as outdated.
    |
    */

    'timeframe_limit' => (int) env('GUARD_TIMEFRAME_LIMIT', 1200),

];
