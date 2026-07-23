<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $tenant = app(Tenancy::class)->current();

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
}
