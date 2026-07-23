<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tenant::with('tenantModules')->latest();

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        return view('admin.tenants.index', [
            'tenants' => $query->get(),
            'search' => $search ?? '',
            'activeStatus' => $status ?? '',
            'statuses' => TenantStatus::cases(),
        ]);
    }

    public function show(Tenant $workspace): View
    {
        $workspace->load('tenantModules');

        return view('admin.tenants.show', [
            'workspace' => $workspace,
            'modules' => Module::cases(),
            // Central admin routes have no tenant context resolved, so the
            // User model's TenantScope would otherwise scope this to
            // "central users only" and silently hide every tenant user —
            // this is the deliberate, explicit cross-tenant read the scope's
            // own design expects for admin tooling.
            'users' => $workspace->users()->withoutGlobalScope(TenantScope::class)->orderBy('name')->get(),
        ]);
    }

    public function updateModules(Request $request, Tenant $workspace): RedirectResponse
    {
        $selected = $request->input('modules', []);

        foreach (Module::cases() as $module) {
            $workspace->tenantModules()->updateOrCreate(
                ['module' => $module->value],
                ['is_enabled' => in_array($module->value, $selected, true)],
            );
        }

        return back()->with('status', 'Packages updated.');
    }
}
