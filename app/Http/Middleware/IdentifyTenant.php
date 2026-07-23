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
