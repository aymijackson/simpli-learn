<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

/**
 * Writes sign-in activity to the activity log: successful and failed
 * logins, logouts and password resets.
 */
class RecordAuthActivity
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'login',
            Failed::class => 'failed',
            Logout::class => 'logout',
            PasswordReset::class => 'passwordReset',
        ];
    }

    public function login(Login $event): void
    {
        // Starting/stopping impersonation logs its own entry; don't also count
        // either switch of account as a fresh sign-in.
        if (session()->has('impersonator_id') || request()->attributes->get('activity.skip_login')) {
            return;
        }

        ActivityLog::record('auth.login', 'Signed in', actor: $event->user);
    }

    public function failed(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? '');

        ActivityLog::record(
            'auth.failed',
            $event->user ? 'Failed sign-in (wrong password)' : "Failed sign-in for unknown email {$email}",
            properties: ['email' => $email],
            actor: $event->user,
        );
    }

    public function logout(Logout $event): void
    {
        if ($event->user) {
            ActivityLog::record('auth.logout', 'Signed out', actor: $event->user);
        }
    }

    public function passwordReset(PasswordReset $event): void
    {
        ActivityLog::record('auth.password_reset', 'Reset their password using an emailed link', actor: $event->user);
    }
}
