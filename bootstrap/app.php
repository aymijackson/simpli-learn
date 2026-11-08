<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\ContentSharing;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureTenantOwner;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\Monitoring\ErrorAlerter;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Http\Middleware\EnsureCanTeach;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            IdentifyTenant::class,
            SecurityHeaders::class,
            EnsureAccountIsActive::class,
            SetLocale::class,
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

        // Content sharing must be settled before route model binding looks up
        // {course}/{exam}, or a shared course would 404 (or an owner could bind
        // another workspace's course in the back office).
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: ContentSharing::class,
        );

        $middleware->alias([
            'api.token' => AuthenticateApiToken::class,
            'content' => ContentSharing::class,
            'module' => EnsureModuleEnabled::class,
            'two-factor' => RequireTwoFactor::class,
        ]);

        // Every owner-only route (team, manage pages in all packages) is an
        // owner check plus the two-step login requirement.
        // Owners, and members assigned to teach a course: only the pages for
        // course content, reports and marking (each also checks the course).
        $middleware->group('teach', [
            EnsureCanTeach::class,
            RequireTwoFactor::class,
            ContentSharing::class.':strict',
        ]);

        $middleware->group('owner', [
            EnsureTenantOwner::class,
            RequireTwoFactor::class,
            // The back office only ever sees the workspace's own content.
            ContentSharing::class.':strict',
        ]);

        // Payment gateway webhooks are unauthenticated server-to-server calls
        // from Stripe/Paystack/Flutterwave — they carry no CSRF token and are
        // protected instead by per-gateway signature verification inside
        // WebhookController.
        $middleware->preventRequestForgery(except: ['webhooks/certificates/*', 'webhooks/courses/*', 'webhooks/library/*', 'csp-report']);

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
        // Email platform admins about errors in production (throttled). The
        // error is still logged as usual.
        $exceptions->report(function (Throwable $e) {
            ErrorAlerter::handle($e);
        });

        // The API always answers in JSON (errors included), whatever Accept says.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Don't name internal classes ("No query results for model …") to API callers.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            return $request->is('api/*') ? response()->json(['message' => 'Not found.'], 404) : null;
        });
    })->create();
