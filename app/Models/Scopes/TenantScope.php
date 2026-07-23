<?php

namespace App\Models\Scopes;

use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenancy = app(Tenancy::class);

        if ($tenancy->check()) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenancy->id());

            return;
        }

        // No tenant resolved (e.g. the central/admin domain, an artisan
        // command, a seeder): only central rows (tenant_id IS NULL) are
        // visible by default. Code that genuinely needs cross-tenant access
        // (an admin report, a maintenance command) should opt in explicitly
        // via Model::withoutGlobalScope(TenantScope::class) rather than
        // relying on an implicit "console" bypass here.
        $builder->whereNull($model->qualifyColumn('tenant_id'));
    }
}
