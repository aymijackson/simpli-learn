<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use App\Support\WorkspaceAccounts;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /** Session key holding the accounts someone may choose between after the central password step. */
    private const CHOICE_KEY = 'login.workspace_choice';

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
                'email' => __('Too many login attempts. Please try again in :minutes :noun.', ['minutes' => (int) ceil($seconds / 60), 'noun' => trans_choice('minute|minutes', (int) ceil($seconds / 60))]),
            ]);
        }

        // Check the password without signing in yet: people with two-step
        // login must pass the code challenge before a session is created.
        $guard = Auth::guard('web');

        if (! $guard->validate($credentials)) {
            // On the central page, the person may be a workspace member who
            // doesn't remember their workspace's address: look for their
            // accounts whose password matches, and take them there.
            $workspaceAccounts = $tenant ? collect() : WorkspaceAccounts::matching($credentials['email'], $credentials['password']);

            if ($workspaceAccounts->isEmpty()) {
                RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
                event(new Failed('web', $guard->getLastAttempted(), $credentials));

                return back()->withErrors([
                    'email' => __('These credentials do not match our records.'),
                ])->onlyInput('email');
            }

            RateLimiter::clear($throttleKey);

            if ($workspaceAccounts->count() === 1) {
                return $this->signInToWorkspace($request, $workspaceAccounts->first(), $request->boolean('remember'));
            }

            // The same email and password in several workspaces: let them choose.
            $request->session()->put(self::CHOICE_KEY, [
                'user_ids' => $workspaceAccounts->pluck('id')->all(),
                'remember' => $request->boolean('remember'),
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]);

            return redirect()->route('login.workspace');
        }

        RateLimiter::clear($throttleKey);
        $user = $guard->getLastAttempted();

        if ($user->isDeactivated()) {
            ActivityLog::record('auth.failed', 'Sign-in blocked: account is deactivated', actor: $user);

            return back()->withErrors([
                'email' => __('This account has been deactivated. Contact your workspace administrator.'),
            ])->onlyInput('email');
        }

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

    /** The workspaces whose accounts matched, for someone choosing which one to enter. */
    public function chooseWorkspace(Request $request): View|RedirectResponse
    {
        $accounts = $this->pendingChoice($request);

        if ($accounts->isEmpty()) {
            return redirect()->route('login')->withErrors(['email' => __('Your sign-in expired. Please enter your password again.')]);
        }

        return view('auth.choose-workspace', ['accounts' => $accounts]);
    }

    public function enterWorkspace(Request $request): RedirectResponse
    {
        $accounts = $this->pendingChoice($request);
        $user = $accounts->firstWhere('id', (int) $request->input('account'));

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => __('Your sign-in expired. Please enter your password again.')]);
        }

        $remember = (bool) $request->session()->get(self::CHOICE_KEY.'.remember');
        $request->session()->forget(self::CHOICE_KEY);

        return $this->signInToWorkspace($request, $user, $remember);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $tenant = app(Tenancy::class)->current();

        return redirect(route($tenant ? 'tenant.login' : 'home'));
    }

    /**
     * Finish signing a workspace member in from the central page: the same
     * checks as their workspace's own login page, then on to their workspace.
     */
    private function signInToWorkspace(Request $request, User $user, bool $remember): RedirectResponse
    {
        $workspace = $user->tenant;

        // A page remembered from the central site (say /admin) isn't theirs to return to.
        $request->session()->forget('url.intended');

        if ($user->isDeactivated()) {
            ActivityLog::record('auth.failed', 'Sign-in blocked: account is deactivated', actor: $user);

            return redirect()->route('login')->withErrors([
                'email' => __('This account has been deactivated. Contact your workspace administrator.'),
            ])->onlyInput('email');
        }

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor.login', [
                'user_id' => $user->id,
                'remember' => $remember,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route('tenant.two-factor.challenge', $workspace->slug);
        }

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        return redirect()->route('tenant.home', $workspace->slug);
    }

    /** @return Collection<int, User> accounts still waiting to be chosen (none once expired) */
    private function pendingChoice(Request $request): Collection
    {
        $pending = $request->session()->get(self::CHOICE_KEY);

        if (! $pending || $pending['expires_at'] < now()->timestamp) {
            return collect();
        }

        // Re-check status: a workspace or account may have changed since the password step.
        return User::withoutGlobalScope(TenantScope::class)
            ->whereIn('id', $pending['user_ids'])
            ->whereHas('tenant', fn ($query) => $query->where('status', TenantStatus::Active))
            ->with('tenant')
            ->orderBy('id')
            ->get();
    }

    private function throttleKey(Request $request, ?int $tenantId): string
    {
        return 'login:'.($tenantId ?? 'central').':'.Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip();
    }
}
