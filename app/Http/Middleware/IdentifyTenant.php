<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenant');

        if ($slug === null) {
            // Tenancy is registered as an app singleton, not request-scoped —
            // without this, a request with no {tenant} segment would
            // silently retain whatever tenant a PRIOR request in the same
            // process last set (harmless under php-fpm/one-process-per-
            // request, but a real cross-request leak under Octane/long-
            // running workers, and reproducible today in feature tests that
            // issue multiple requests per test method).
            app(Tenancy::class)->forget();

            return $next($request);
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if (! $tenant || ! $tenant->isActive()) {
            abort(404);
        }

        app(Tenancy::class)->set($tenant);

        URL::defaults(['tenant' => $tenant->slug]);

        return $next($request);
    }
}
