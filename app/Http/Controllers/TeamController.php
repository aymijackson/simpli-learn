<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        return view('team.index', [
            'members' => app(Tenancy::class)->current()->users()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('team.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app(Tenancy::class)->current();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('tenant.team.index')->with('status', 'Team member added.');
    }

    public function update(Request $request, string $tenant, User $member): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ]);

        if ($member->isOwner() && $validated['role'] !== UserRole::Owner->value && $this->isLastOwner($member)) {
            return back()->withErrors(['role' => 'You cannot demote the only owner.']);
        }

        $member->update(['role' => $validated['role']]);

        return redirect()->route('tenant.team.index')->with('status', 'Role updated.');
    }

    public function destroy(string $tenant, User $member): RedirectResponse
    {
        if ($member->id === auth()->id()) {
            return back()->withErrors(['member' => 'You cannot remove yourself.']);
        }

        if ($member->isOwner() && $this->isLastOwner($member)) {
            return back()->withErrors(['member' => 'You cannot remove the only owner.']);
        }

        $member->delete();

        return redirect()->route('tenant.team.index')->with('status', 'Team member removed.');
    }

    private function isLastOwner(User $member): bool
    {
        return $member->tenant->users()->where('role', UserRole::Owner)->count() <= 1;
    }
}
