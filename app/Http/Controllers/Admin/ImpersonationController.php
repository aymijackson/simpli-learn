<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->isActive(), 404);

        $owner = $workspace->users()
            ->withoutGlobalScope(TenantScope::class)
            ->where('role', UserRole::Owner)
            ->first();

        abort_unless($owner, 404, 'This workspace has no owner to manage as.');

        session(['impersonator_id' => auth()->id()]);
        Auth::login($owner);

        return redirect()
            ->route('tenant.home', $workspace)
            ->with('status', "You're now managing {$workspace->name}.");
    }

    public function stop(): RedirectResponse
    {
        $originalId = session()->pull('impersonator_id');

        abort_unless($originalId, 403);

        $original = User::withoutGlobalScope(TenantScope::class)->find($originalId);

        abort_unless($original, 403);

        Auth::login($original);

        return redirect()->route('admin.dashboard')->with('status', 'Stopped managing workspace.');
    }
}
