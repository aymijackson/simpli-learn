<?php

namespace App\Support;

use App\Models\JobTitle;
use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;

/**
 * Saves the "Recommended for" job titles from <x-job-titles-picker>: the
 * ticked titles (only this workspace's) plus, for owners, any new ones typed
 * in. Only runs when the form actually included the picker.
 */
class JobTitleSync
{
    public static function apply(Request $request, BelongsToMany $relation): void
    {
        if (! $request->has('job_titles_submitted')) {
            return;
        }

        $tenantId = app(Tenancy::class)->id();
        $ids = JobTitle::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->whereIn('id', array_map('intval', (array) $request->input('job_titles', [])))
            ->pluck('id');

        if ($request->user()?->isOwner() && trim((string) $request->input('new_job_titles')) !== '') {
            $ids = $ids->merge(JobTitle::findOrCreateMany([$request->input('new_job_titles')], $tenantId)->pluck('id'));
        }

        $relation->sync($ids->unique()->values()->all());
    }
}
