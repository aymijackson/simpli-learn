<?php

namespace App\Support\Monitoring;

use App\Models\User;
use App\Notifications\ErrorOccurred;
use App\Support\Tenancy\Tenancy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Emails platform admins about errors reported in production. Each distinct
 * error (class + file + line) is sent at most once per throttle window, with
 * a count of how often it happened meanwhile. Never includes form input,
 * and never lets a mail failure cause another alert.
 */
class ErrorAlerter
{
    private static bool $sending = false;

    public static function handle(Throwable $e): void
    {
        if (self::$sending || ! config('monitoring.alerts_enabled')) {
            return;
        }

        self::$sending = true;

        try {
            $recipients = self::recipients();
            if ($recipients === []) {
                return;
            }

            $key = 'monitoring:alert:'.sha1(get_class($e).'|'.$e->getFile().'|'.$e->getLine());
            $window = max(1, (int) config('monitoring.alert_throttle_minutes')) * 60;

            // Count every occurrence; only the first in each window emails.
            Cache::add($key.':count', 0, $window * 4);
            $occurrences = (int) Cache::increment($key.':count');
            if (! Cache::add($key.':sent', true, $window)) {
                return;
            }
            Cache::put($key.':count', 0, $window * 4);

            Notification::route('mail', $recipients)->notify(new ErrorOccurred(self::summary($e, $occurrences)));
        } catch (Throwable) {
            // Alerting must never make things worse; the error is still logged.
        } finally {
            self::$sending = false;
        }
    }

    /** @return array<int, string> */
    public static function recipients(): array
    {
        $configured = array_filter(array_map('trim', explode(',', (string) config('monitoring.alert_email'))));
        if ($configured !== []) {
            return array_values($configured);
        }

        return User::withoutGlobalScopes()->whereNull('tenant_id')->whereNull('deactivated_at')->pluck('email')->all();
    }

    /** @return array<string, string> */
    public static function summary(Throwable $e, int $occurrences = 1): array
    {
        // A web request is one with a matched route; otherwise it's a command.
        $request = app()->bound('request') && request()->route() ? request() : null;

        return array_filter([
            'error' => get_class($e),
            'message' => mb_strimwidth($e->getMessage(), 0, 500, '…'),
            'where' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $e->getFile()).':'.$e->getLine(),
            // The route pattern, not the URL: paths can carry secrets (reset tokens).
            'page' => $request ? $request->method().' '.($request->route()?->uri() ?? $request->path()) : 'console: '.implode(' ', array_slice($_SERVER['argv'] ?? [], 1)),
            'workspace' => app(Tenancy::class)->current()?->slug,
            'user' => $request?->user() ? '#'.$request->user()->id : null,
            'occurrences' => $occurrences > 1 ? $occurrences.' times since the last alert' : null,
            'time' => now()->toDayDateTimeString(),
            'app' => config('app.url'),
        ]);
    }
}
