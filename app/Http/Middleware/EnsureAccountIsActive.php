<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out anyone whose account was deactivated while they were signed in.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDeactivated() && ! $request->session()->has('impersonator_id')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $tenant = app(Tenancy::class)->current();

            return redirect()->route($tenant ? 'tenant.login' : 'login')
                ->withErrors(['email' => 'This account has been deactivated. Contact your workspace administrator.']);
        }

        return $next($request);
    }
}
