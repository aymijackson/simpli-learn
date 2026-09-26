<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class ImpersonationController extends Controller
{
    public function start(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->isActive(), 404);

        $owner = $workspace->users()
            ->withoutGlobalScope(TenantScope::class)
            ->where('role', UserRole::Owner)
            ->whereNull('deactivated_at')
            ->first();

        abort_unless($owner, 404, 'This workspace has no owner to manage as.');

        ActivityLog::record('impersonation.started', "Started managing {$workspace->name} as {$owner->name}", $workspace, tenantId: $workspace->id);

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

        ActivityLog::record('impersonation.stopped', "Stopped managing the workspace and returned to the admin account", actor: $original);
        // The Login event below is the admin returning, not a fresh sign-in.
        request()->attributes->set('activity.skip_login', true);

        Auth::login($original);

        return redirect()->route('admin.dashboard')->with('status', 'Stopped managing workspace.');
    }
}
