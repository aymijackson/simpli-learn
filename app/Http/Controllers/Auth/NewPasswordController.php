<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Invitations;
use App\Support\Tenancy\Tenancy;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        $tenant = app(Tenancy::class)->current();

        return view('auth.reset-password', [
            'action' => route($tenant ? 'tenant.password.store' : 'password.store'),
            'token' => $request->route('token'),
            'email' => $request->string('email'),
            'invite' => $request->boolean('invite'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // Invitation links use their own broker (7-day expiry); see App\Support\Invitations.
        $broker = Password::broker($request->boolean('invite') ? Invitations::BROKER : null);

        $status = $broker->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($validated) {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        $tenant = app(Tenancy::class)->current();

        return redirect()
            ->route($tenant ? 'tenant.login' : 'login', $tenant ?: [])
            ->with('status', $request->boolean('invite') ? 'Your password is set. Log in to get started.' : __($status));
    }
}
