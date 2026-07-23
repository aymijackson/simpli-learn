<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'counts' => [
                'pending' => Tenant::where('status', TenantStatus::Pending)->count(),
                'active' => Tenant::where('status', TenantStatus::Active)->count(),
                'suspended' => Tenant::where('status', TenantStatus::Suspended)->count(),
                'rejected' => Tenant::where('status', TenantStatus::Rejected)->count(),
            ],
            'pendingTenants' => Tenant::with('tenantModules')->where('status', TenantStatus::Pending)->latest()->get(),
        ]);
    }
}
