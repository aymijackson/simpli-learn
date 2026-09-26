<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 300;

    public function create(): View
    {
        $tenant = app(Tenancy::class)->current();

        return view('auth.login', [
            'action' => route($tenant ? 'tenant.login' : 'login'),
            'forgotPasswordRoute' => route($tenant ? 'tenant.password.request' : 'password.request', $tenant ?: []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $tenant = app(Tenancy::class)->current();
        $throttleKey = $this->throttleKey($request, $tenant?->id);

        // Slow down password guessing: 5 failed attempts per email + IP (per
        // workspace), then a lockout that tells the person when to retry.
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.ceil($seconds / 60).' '.Str::plural('minute', (int) ceil($seconds / 60)).'.',
            ]);
        }

        // Check the password without signing in yet: people with two-step
        // login must pass the code challenge before a session is created.
        $guard = Auth::guard('web');

        if (! $guard->validate($credentials)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            event(new Failed('web', $guard->getLastAttempted(), $credentials));

            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $user = $guard->getLastAttempted();

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor.login', [
                'user_id' => $user->id,
                'remember' => $request->boolean('remember'),
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route($tenant ? 'tenant.two-factor.challenge' : 'two-factor.challenge');
        }

        $guard->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route($tenant ? 'tenant.home' : 'admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $tenant = app(Tenancy::class)->current();

        return redirect(route($tenant ? 'tenant.login' : 'home'));
    }

    private function throttleKey(Request $request, ?int $tenantId): string
    {
        return 'login:'.($tenantId ?? 'central').':'.Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip();
    }
}
