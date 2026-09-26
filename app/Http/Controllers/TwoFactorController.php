<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Tenancy\Tenancy;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Turning two-step login on and off from "Profile & security".
 *
 * Setup is two-phase: `enable` stores a new (unconfirmed) secret and shows
 * the QR code; it only takes effect once `confirm` receives a valid code,
 * so a half-finished setup can never lock anyone out.
 */
class TwoFactorController extends Controller
{
    public function enable(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret' => TwoFactor::generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $this->backToSection($tenancy);
    }

    public function confirm(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $user = $request->user();
        $request->validateWithBag('twoFactor', ['code' => ['required', 'string']]);

        if (! $user->two_factor_secret || $user->two_factor_confirmed_at) {
            return $this->backToSection($tenancy);
        }

        if (! TwoFactor::verify($user, $user->two_factor_secret, (string) $request->input('code'))) {
            return $this->backToSection($tenancy)->withErrors(['code' => 'That code is not valid. Check the time on your phone is set automatically, then try the newest code.'], 'twoFactor');
        }

        $codes = TwoFactor::generateRecoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();
        ActivityLog::record('security.two_factor_enabled', 'Turned on two-step login');

        return $this->backToSection($tenancy)
            ->with('status', 'Two-step login is on.')
            ->with('recovery_codes', $codes);
    }

    public function cancel(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $user = $request->user();

        if (! $user->two_factor_confirmed_at) {
            $user->forceFill(['two_factor_secret' => null])->save();
        }

        return $this->backToSection($tenancy);
    }

    public function disable(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        ActivityLog::record('security.two_factor_disabled', 'Turned off two-step login');

        return $this->backToSection($tenancy)->with('status', 'Two-step login is off.');
    }

    public function regenerateRecoveryCodes(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']]);
        $user = $request->user();

        abort_unless($user->hasTwoFactorEnabled(), 404);

        $codes = TwoFactor::generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        ActivityLog::record('security.recovery_codes_regenerated', 'Generated new two-step recovery codes');

        return $this->backToSection($tenancy)
            ->with('status', 'New recovery codes generated. Your old codes no longer work.')
            ->with('recovery_codes', $codes);
    }

    private function backToSection(Tenancy $tenancy): RedirectResponse
    {
        return redirect(route($tenancy->current() ? 'tenant.profile.edit' : 'profile.edit').'#two-factor');
    }
}
