<?php

namespace App\Console\Commands;

use App\Notifications\ErrorOccurred;
use App\Support\Monitoring\ErrorAlerter;
use App\Support\Monitoring\HealthCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Sends a sample error alert and prints the health checks — to verify setup on a server. */
class MonitoringTestAlert extends Command
{
    protected $signature = 'monitoring:test-alert';

    protected $description = 'Send a test error-alert email to the alert recipients and show the health checks';

    public function handle(): int
    {
        foreach (HealthCheck::run() as $name => $check) {
            $this->line(sprintf('  %-10s %s  %s', $name, $check['ok'] ? '<info>ok</info>' : '<error>FAIL</error>', $check['note']));
        }
        $this->newLine();

        $recipients = ErrorAlerter::recipients();
        if ($recipients === []) {
            $this->error('No recipients: set MONITORING_ALERT_EMAIL or create a platform admin account.');

            return self::FAILURE;
        }

        if (! config('monitoring.alerts_enabled')) {
            $this->warn('Alerts are OFF (MONITORING_ALERTS). Sending this test anyway; real errors will not be emailed until you turn them on.');
        }

        try {
            Notification::route('mail', $recipients)->notify(new ErrorOccurred(
                ErrorAlerter::summary(new \RuntimeException('This is a test alert from `php artisan monitoring:test-alert`. No action needed.'))
            ));
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e->getMessage());
            $this->line('Check the MAIL_* settings (php artisan mail:test can help).');

            return self::FAILURE;
        }

        $this->info('Test alert sent to: '.implode(', ', $recipients));

        return self::SUCCESS;
    }
}
