<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Models\ApiToken;
use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * API requests carry "Authorization: Bearer elib_…". The key decides the
 * workspace (the API never sees another workspace's data) and whether
 * writes are allowed. 120 requests a minute per key.
 */
class AuthenticateApiToken
{
    public const PER_MINUTE = 120;

    public function handle(Request $request, Closure $next, string $ability = 'read'): Response
    {
        $token = ApiToken::findActive($request->bearerToken());
        $tenant = $token?->tenant;

        if (! $token || ! $tenant || $tenant->status !== TenantStatus::Active) {
            return response()->json(['message' => 'Missing or invalid API key.'], 401);
        }
        if ($ability === 'write' && ! $token->can_write) {
            return response()->json(['message' => 'This API key is read-only.'], 403);
        }

        $key = 'api:'.$token->id;
        if (RateLimiter::tooManyAttempts($key, self::PER_MINUTE)) {
            return response()->json(['message' => 'Too many requests. Slow down and retry shortly.'], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($key));
        }
        RateLimiter::hit($key, 60);

        $tenancy = app(Tenancy::class);
        $tenancy->set($tenant);
        $tenancy->shareContent(false); // only the workspace's own content
        $request->attributes->set('api_token', $token);

        // Updating on every request would be a write per call; once a minute is plenty.
        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
