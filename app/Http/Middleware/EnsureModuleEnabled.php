<?php

namespace App\Http\Middleware;

use App\Enums\Module;
use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenancy = app(Tenancy::class);
        $tenant = $tenancy->current();

        if (! $tenant || ! $tenant->hasModule(Module::from($module))) {
            abort(403, 'This module is not enabled for your organization.');
        }

        return $next($request);
    }
}
