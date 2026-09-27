<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Content that another workspace may share with the current one (a public
 * course and everything inside it). When sharing is allowed for the request,
 * TenantScope shows the current workspace's own rows plus the rows this
 * constraint matches. It must only ever match published, shared content.
 */
interface SharedAcrossTenants
{
    public function sharedWithTenant(Builder $query, int $tenantId): void;
}
