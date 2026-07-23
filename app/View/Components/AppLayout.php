<?php

namespace App\View\Components;

use App\Models\Tenant;
use App\Support\Tenancy\Tenancy;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class AppLayout extends Component
{
    public Tenant $tenant;

    public Collection $enabledModules;

    public function __construct(public ?string $title = null)
    {
        $this->tenant = app(Tenancy::class)->current();
        $this->enabledModules = $this->tenant->tenantModules()->where('is_enabled', true)->get();
    }

    public function render()
    {
        return view('components.layout');
    }
}
