<?php

namespace App\Support\Monitoring;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Checks the parts the platform can't run without. Each check reports ok
 * plus a short, non-sensitive note (no hostnames, paths or credentials).
 */
class HealthCheck
{
    public const HEARTBEAT_KEY = 'monitoring:scheduler-heartbeat';

    /** @return array<string, array{ok: bool, note: string}> */
    public static function run(): array
    {
        return [
            'database' => self::attempt(function () {
                DB::select('select 1');

                return 'Connected';
            }),
            'storage' => self::attempt(function () {
                $file = storage_path('framework/health-'.Str::random(8));
                if (file_put_contents($file, 'ok') === false) {
                    throw new \UnexpectedValueException('Storage is not writable');
                }
                unlink($file);

                return 'Writable';
            }),
            'cache' => self::attempt(function () {
                $key = 'monitoring:health-'.Str::random(8);
                Cache::put($key, 'ok', 10);
                $ok = Cache::get($key) === 'ok';
                Cache::forget($key);
                if (! $ok) {
                    throw new \UnexpectedValueException('Cache did not return the value');
                }

                return 'Working';
            }),
            'scheduler' => self::scheduler(),
        ];
    }

    public static function healthy(array $checks): bool
    {
        return collect($checks)->every(fn ($check) => $check['ok']);
    }

    /** Written every minute by the scheduler (routes/console.php). */
    public static function beat(): void
    {
        Cache::forever(self::HEARTBEAT_KEY, now()->toIso8601String());
    }

    /** @return array{ok: bool, note: string} */
    private static function scheduler(): array
    {
        return self::attempt(function () {
            $last = Cache::get(self::HEARTBEAT_KEY);
            if (! $last) {
                throw new \UnexpectedValueException('Has never run — add the cron job for `php artisan schedule:run`');
            }
            $last = Carbon::parse($last);
            if ($last->lt(now()->subMinutes((int) config('monitoring.scheduler_stale_minutes')))) {
                throw new \UnexpectedValueException('Last ran '.$last->diffForHumans().' — check the cron job');
            }

            return 'Last ran '.$last->diffForHumans();
        });
    }

    /** @return array{ok: bool, note: string} */
    private static function attempt(callable $check): array
    {
        try {
            return ['ok' => true, 'note' => $check()];
        } catch (Throwable $e) {
            // Only our own messages are shown; driver errors can name hosts or paths.
            return ['ok' => false, 'note' => $e instanceof \UnexpectedValueException ? $e->getMessage() : 'Failed ('.class_basename($e).')'];
        }
    }
}
