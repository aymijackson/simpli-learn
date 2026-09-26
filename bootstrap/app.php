<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureTenantOwner;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Support\Tenancy\Tenancy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            IdentifyTenant::class,
            SecurityHeaders::class,
            EnsureAccountIsActive::class,
        ]);

        // Tenant resolution must happen before auth/guest checks, since which
        // user table rows are even visible depends on the resolved tenant.
        // Without this, Laravel's default priority list runs 'auth' ahead of
        // any custom middleware not listed in it, regardless of where it's
        // declared in the route/group nesting.
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: IdentifyTenant::class,
        );

        $middleware->alias([
            'module' => EnsureModuleEnabled::class,
            'owner' => EnsureTenantOwner::class,
        ]);

        // Payment gateway webhooks are unauthenticated server-to-server calls
        // from Stripe/Paystack/Flutterwave — they carry no CSRF token and are
        // protected instead by per-gateway signature verification inside
        // WebhookController.
        $middleware->validateCsrfTokens(except: ['webhooks/certificates/*', 'webhooks/courses/*', 'webhooks/library/*']);

        // Laravel's defaults know nothing about tenant context: an
        // unauthenticated request would otherwise always bounce to the
        // central /login (useless for a tenant user, whose credentials
        // never work there), and an authenticated request would bounce to
        // '/' instead of the workspace/dashboard the user actually has.
        $middleware->redirectGuestsTo(function (Request $request) {
            $tenant = app(Tenancy::class)->current();

            return $tenant ? route('tenant.login', $tenant) : route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            $tenant = app(Tenancy::class)->current();

            return $tenant ? route('tenant.home', $tenant) : route('admin.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
