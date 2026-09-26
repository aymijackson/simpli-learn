<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WorkspaceApproved;
use App\Notifications\WorkspaceRejected;
use App\Support\SafeNotifier;
use Illuminate\Http\RedirectResponse;
use App\Models\ActivityLog;

class TenantApprovalController extends Controller
{
    public function approve(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->isPending(), 404);

        $workspace->update(['status' => TenantStatus::Active]);
        $emailed = SafeNotifier::send($this->owners($workspace), new WorkspaceApproved($workspace));

        ActivityLog::record('workspace.approved', "Approved workspace {$workspace->name}", $workspace);

        return back()->with('status', "{$workspace->name} has been approved.".($emailed ? '' : ' (The owner could not be emailed — check the mail settings.)'));
    }

    public function reject(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->isPending(), 404);

        $workspace->update(['status' => TenantStatus::Rejected]);
        SafeNotifier::send($this->owners($workspace), new WorkspaceRejected($workspace));

        ActivityLog::record('workspace.rejected', "Rejected workspace {$workspace->name}", $workspace);

        return back()->with('status', "{$workspace->name} has been rejected.");
    }

    public function suspend(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->isActive(), 404);

        $workspace->update(['status' => TenantStatus::Suspended]);

        ActivityLog::record('workspace.suspended', "Suspended workspace {$workspace->name}", $workspace);

        return back()->with('status', "{$workspace->name} has been suspended.");
    }

    public function reactivate(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->status === TenantStatus::Suspended, 404);

        $workspace->update(['status' => TenantStatus::Active]);

        ActivityLog::record('workspace.reactivated', "Reactivated workspace {$workspace->name}", $workspace);

        return back()->with('status', "{$workspace->name} has been reactivated.");
    }

    /** Owners of a workspace, looked up outside tenant scoping (this runs on the central admin). */
    private function owners(Tenant $workspace)
    {
        return User::withoutGlobalScopes()->where('tenant_id', $workspace->id)->where('role', 'owner')->get();
    }
}
