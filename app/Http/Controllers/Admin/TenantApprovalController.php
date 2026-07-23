<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

class TenantApprovalController extends Controller
{
    public function approve(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->isPending(), 404);

        $workspace->update(['status' => TenantStatus::Active]);

        return back()->with('status', "{$workspace->name} has been approved.");
    }

    public function reject(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->isPending(), 404);

        $workspace->update(['status' => TenantStatus::Rejected]);

        return back()->with('status', "{$workspace->name} has been rejected.");
    }

    public function suspend(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->isActive(), 404);

        $workspace->update(['status' => TenantStatus::Suspended]);

        return back()->with('status', "{$workspace->name} has been suspended.");
    }

    public function reactivate(Tenant $workspace): RedirectResponse
    {
        abort_unless($workspace->status === TenantStatus::Suspended, 404);

        $workspace->update(['status' => TenantStatus::Active]);

        return back()->with('status', "{$workspace->name} has been reactivated.");
    }
}
