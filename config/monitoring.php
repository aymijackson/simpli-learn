<?php

return [

    /*
    | Email platform admins when an error is reported. On by default only in
    | production, so local development and tests stay quiet.
    */
    'alerts_enabled' => (bool) env('MONITORING_ALERTS', env('APP_ENV') === 'production'),

    /*
    | Where alerts go (comma-separated). Empty = every platform admin account.
    */
    'alert_email' => env('MONITORING_ALERT_EMAIL'),

    /*
    | The same error (same class, file and line) is emailed at most once per
    | this many minutes; repeats in between are counted in the next alert.
    */
    'alert_throttle_minutes' => (int) env('MONITORING_ALERT_THROTTLE', 15),

    /*
    | The scheduler (cron running `schedule:run` every minute) is reported as
    | down when it hasn't run for this many minutes.
    */
    'scheduler_stale_minutes' => (int) env('MONITORING_SCHEDULER_STALE', 5),

];
