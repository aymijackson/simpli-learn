<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Workspace owners and platform admins can reach fan data, payments and
 * every workspace setting, so they must turn on two-step login before
 * using the back office. Everyone else keeps it optional.
 *
 * Switch off with REQUIRE_TWO_FACTOR=false (config/security.php).
 */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! config('security.require_two_factor')
            || ! $user
            || $user->hasTwoFactorEnabled()
            || $request->session()->has('impersonator_id')) {
            return $next($request);
        }

        $route = app(Tenancy::class)->current() ? 'tenant.profile.edit' : 'profile.edit';

        return redirect(route($route).'#two-factor')->with(
            'status',
            'Before you can manage '.(app(Tenancy::class)->current() ? 'this workspace' : 'the platform')
                .', please turn on two-step login below. It takes about a minute and protects everyone\'s data if your password is ever stolen.'
        );
    }
}
