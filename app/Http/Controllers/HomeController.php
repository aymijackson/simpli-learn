<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\Tenancy;

class HomeController extends Controller
{
    public function __invoke(Tenancy $tenancy)
    {
        $tenant = $tenancy->current();

        return view('home', [
            'tenant' => $tenant,
            'enabledModules' => $tenant->tenantModules()->where('is_enabled', true)->get(),
        ]);
    }
}
