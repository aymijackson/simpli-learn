<?php

namespace App\Providers;

use App\Listeners\RecordAuthActivity;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Tenancy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(RecordAuthActivity::class);

        // The stock notification always links to the central `password.reset`
        // route. A tenant user's reset link needs the /t/{tenant} prefix, or
        // it 404s before the token is ever checked.
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            $params = ['token' => $token, 'email' => $user->email];

            $route = $user->tenant_id
                ? route('tenant.password.reset', ['tenant' => $user->tenant, ...$params], false)
                : route('password.reset', $params, false);

            return url($route);
        });
    }
}
