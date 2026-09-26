<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Second step of sign-in for people with two-step login turned on. The
 * password step (AuthenticatedSessionController) leaves a short-lived
 * "pending" marker in the session; nothing is signed in until this passes.
 */
class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(Request $request, Tenancy $tenancy): View|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route($tenancy->current() ? 'tenant.login' : 'login');
        }

        return view('auth.two-factor-challenge', [
            'action' => route($tenancy->current() ? 'tenant.two-factor.challenge' : 'two-factor.challenge'),
        ]);
    }

    public function store(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $loginRoute = $tenancy->current() ? 'tenant.login' : 'login';
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route($loginRoute)->withErrors(['email' => 'Your sign-in expired. Please enter your password again.']);
        }

        $request->validate([
            'code' => ['nullable', 'string', 'max:20', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:40'],
        ]);

        $key = "two-factor-challenge:{$user->id}";
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $request->session()->forget('two_factor.login');

            return redirect()->route($loginRoute)->withErrors(['email' => 'Too many incorrect codes. Please sign in again.']);
        }

        $usedRecoveryCode = false;
        $valid = $request->filled('recovery_code')
            ? ($usedRecoveryCode = TwoFactor::useRecoveryCode($user, (string) $request->input('recovery_code')))
            : TwoFactor::verify($user, $user->two_factor_secret, (string) $request->input('code'));

        if (! $valid) {
            RateLimiter::hit($key, 300);
            ActivityLog::record('auth.failed', 'Failed sign-in (wrong two-step code)', actor: $user);

            return back()->withErrors([$request->filled('recovery_code') ? 'recovery_code' : 'code' => 'That code is not valid. Please try again.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->get('two_factor.login.remember');
        $request->session()->forget('two_factor.login');

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        if ($usedRecoveryCode) {
            $left = count($user->two_factor_recovery_codes ?? []);
            ActivityLog::record('security.recovery_code_used', "Signed in with a recovery code ({$left} left)");

            return redirect()->route($tenancy->current() ? 'tenant.profile.edit' : 'profile.edit')
                ->with('status', "You signed in with a recovery code. You have {$left} left — if you've lost your phone, set up two-step login again below.");
        }

        return redirect()->intended(route($tenancy->current() ? 'tenant.home' : 'admin.dashboard'));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('two_factor.login');

        if (! $pending || $pending['expires_at'] < now()->timestamp) {
            return null;
        }

        $user = User::find($pending['user_id']);

        return $user?->hasTwoFactorEnabled() ? $user : null;
    }
}
