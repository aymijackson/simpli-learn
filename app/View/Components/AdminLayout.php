<?php

namespace App\View\Components;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    /** Workspaces waiting for approval, shown as a sidebar badge. */
    public int $pendingTenants;

    public function __construct(public ?string $title = null)
    {
        $this->pendingTenants = Tenant::where('status', TenantStatus::Pending)->count();
    }

    public function render()
    {
        return view('components.admin-layout');
    }
}
