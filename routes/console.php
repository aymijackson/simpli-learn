<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run everything below with one cPanel cron job, every minute:
//   * * * * * cd /home/<user>/<app> && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('courses:send-reminders')->dailyAt('08:00');
Schedule::call(fn () => DatabaseNotification::whereNotNull('read_at')->where('created_at', '<', now()->subDays(90))->delete())
    ->daily()->name('prune-read-notifications');
Schedule::command('model:prune')->daily();
