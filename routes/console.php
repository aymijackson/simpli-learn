<?php

use App\Support\Monitoring\HealthCheck;
use Elibrary\Cbt\Models\ExamProctoringSnapshot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run everything below with one cPanel cron job, every minute:
//   * * * * * cd /home/<user>/<app> && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('courses:send-reminders')->dailyAt('08:00');
Schedule::command('library:process-loans')->hourly()->withoutOverlapping();
Schedule::call(fn () => DatabaseNotification::whereNotNull('read_at')->where('created_at', '<', now()->subDays(90))->delete())
    ->daily()->name('prune-read-notifications');
// Heartbeat for the health check: proves the cron job runs schedule:run.
Schedule::call(fn () => HealthCheck::beat())->everyMinute()->name('monitoring-heartbeat');
Schedule::command('model:prune')->daily();
// Exam webcam photos, once each exam's retention period has passed (model:prune only scans app/Models by default).
Schedule::command('model:prune', ['--model' => [ExamProctoringSnapshot::class]])->dailyAt('03:30')->name('prune-proctoring-photos');
