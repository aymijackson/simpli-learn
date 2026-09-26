<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\NewWorkspaceSignup;
use App\Support\SafeNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:50', 'alpha_dash:ascii', Rule::unique('tenants', 'slug')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => [Rule::in(array_column(Module::cases(), 'value'))],
        ]);

        $tenant = Tenant::create([
            'name' => $validated['organization_name'],
            'slug' => Str::lower($validated['slug']),
            'status' => TenantStatus::Pending,
        ]);

        foreach ($validated['modules'] as $module) {
            $tenant->tenantModules()->create([
                'module' => $module,
                'is_enabled' => true,
            ]);
        }

        User::create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::Owner,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        SafeNotifier::send(
            User::withoutGlobalScopes()->whereNull('tenant_id')->get(),
            new NewWorkspaceSignup($tenant->load('tenantModules'), $validated['name'], $validated['email']),
        );

        return redirect()->route('signup.pending')->with('organization', $tenant->name);
    }

    public function pending(): View
    {
        return view('auth.registration-pending', [
            'organization' => session('organization'),
        ]);
    }
}
