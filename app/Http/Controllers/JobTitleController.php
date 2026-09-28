<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\JobTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Owners keep the workspace's job titles tidy: rename one, merge a
 * duplicate into another (renaming it to an existing title does that), or
 * remove one. People and recommendations follow the change.
 */
class JobTitleController extends Controller
{
    public function index(): View
    {
        return view('team.job-titles', [
            'titles' => JobTitle::query()->withCount(['users', 'courses', 'libraryResources'])->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $tenant, JobTitle $jobTitle): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $name = JobTitle::tidy($validated['name']);
        $existing = JobTitle::findByName($name, $jobTitle->tenant_id);

        if ($existing && $existing->isNot($jobTitle)) {
            $this->merge($jobTitle, $existing);
            ActivityLog::record('team.job_title_merged', "Merged the job title {$jobTitle->name} into {$existing->name}", $existing);

            return back()->with('status', "Merged “{$jobTitle->name}” into “{$existing->name}”.");
        }

        $old = $jobTitle->name;
        $jobTitle->update(['name' => $name, 'key' => JobTitle::keyFor($name)]);
        ActivityLog::record('team.job_title_renamed', "Renamed the job title {$old} to {$name}", $jobTitle);

        return back()->with('status', 'Job title renamed.');
    }

    public function destroy(string $tenant, JobTitle $jobTitle): RedirectResponse
    {
        // People keep their account; they just no longer have this title.
        $jobTitle->delete();
        ActivityLog::record('team.job_title_deleted', "Removed the job title {$jobTitle->name}");

        return back()->with('status', "Removed “{$jobTitle->name}”.");
    }

    /** Moves people and recommendations from one title to another, then deletes it. */
    private function merge(JobTitle $from, JobTitle $into): void
    {
        DB::transaction(function () use ($from, $into) {
            $from->users()->update(['job_title_id' => $into->id]);
            $into->courses()->syncWithoutDetaching($from->courses()->pluck('courses.id'));
            $into->libraryResources()->syncWithoutDetaching($from->libraryResources()->pluck('library_resources.id'));
            $from->delete();
        });
    }
}
