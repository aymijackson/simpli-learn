<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\SingleSignOn;
use App\Support\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * "Continue with Google / Microsoft" on a workspace's sign-in page. The
 * provider's callback URL is the same for every workspace (it's registered
 * once with Google/Microsoft), so the workspace travels in the session.
 */
class SingleSignOnController extends Controller
{
    public function redirect(Request $request, string $tenant, string $provider): RedirectResponse
    {
        $workspace = app(Tenancy::class)->current();
        abort_unless(array_key_exists($provider, SingleSignOn::enabledFor($workspace)), 404);

        $request->session()->put('sso.tenant', $workspace->slug);

        return Socialite::driver($provider)->redirectUrl(route('sso.callback', $provider))->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $workspace = Tenant::where('slug', (string) $request->session()->pull('sso.tenant'))->first();
        abort_unless($workspace && $workspace->status === TenantStatus::Active, 404);
        abort_unless(array_key_exists($provider, SingleSignOn::enabledFor($workspace)), 404);

        $login = fn (string $message) => redirect()->route('tenant.login', $workspace->slug)->withErrors(['email' => $message]);

        try {
            $account = Socialite::driver($provider)->redirectUrl(route('sso.callback', $provider))->user();
        } catch (Throwable) {
            return $login('Sign-in with '.SingleSignOn::PROVIDERS[$provider].' was cancelled or failed. Please try again.');
        }

        $email = Str::lower(trim((string) $account->getEmail()));
        // Google says whether it verified the address; Microsoft work accounts are managed by the organisation.
        $verified = $provider !== 'google' || (bool) ($account->user['email_verified'] ?? $account->user['verified_email'] ?? false);
        if ($email === '' || ! $verified) {
            return $login('Your '.SingleSignOn::PROVIDERS[$provider].' account has no verified email address.');
        }

        $user = User::withoutGlobalScopes()->where('tenant_id', $workspace->id)->whereRaw('lower(email) = ?', [$email])->first();

        if (! $user && SingleSignOn::mayJoin($workspace, $email)) {
            $user = User::withoutGlobalScopes()->create([
                'tenant_id' => $workspace->id,
                'name' => $account->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Str::random(40), // they sign in with SSO; they can set a password via "forgot password"
                'role' => UserRole::Member,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            ActivityLog::record('team.joined', "{$user->name} joined via ".SingleSignOn::PROVIDERS[$provider].' sign-in', $user, actor: $user, tenantId: $workspace->id);
        }

        if (! $user) {
            return $login("There's no account for {$email} in {$workspace->name}. Ask your administrator to invite you.");
        }
        if ($user->isDeactivated()) {
            return $login('This account has been deactivated. Contact your workspace administrator.');
        }

        // Two-step login still applies after SSO.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor.login', [
                'user_id' => $user->id,
                'remember' => false,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route('tenant.two-factor.challenge', $workspace->slug);
        }

        app(Tenancy::class)->set($workspace);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('tenant.home', $workspace->slug));
    }
}
