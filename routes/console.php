<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run everything below with one cPanel cron job, every minute:
//   * * * * * cd /home/<user>/<app> && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('courses:send-reminders')->dailyAt('08:00');
Schedule::command('model:prune')->daily();
