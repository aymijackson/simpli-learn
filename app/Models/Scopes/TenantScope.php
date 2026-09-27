<?php

namespace App\Models\Scopes;

use App\Models\Contracts\SharedAcrossTenants;
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
            $column = $model->qualifyColumn('tenant_id');
            $tenantId = $tenancy->id();

            // Learner-facing pages may also see content shared from other
            // workspaces; the back office never does (see Tenancy::sharesContent).
            if ($model instanceof SharedAcrossTenants && $tenancy->sharesContent()) {
                $builder->where(fn (Builder $query) => $query
                    ->where($column, $tenantId)
                    ->orWhere(fn (Builder $shared) => $model->sharedWithTenant($shared, $tenantId)));

                return;
            }

            $builder->where($column, $tenantId);

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
