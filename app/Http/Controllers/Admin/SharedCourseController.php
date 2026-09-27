<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Elibrary\Lms\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform admins decide which courses reach other workspaces: open to every
 * workspace, or shared with chosen ones. Content stays owned (and editable
 * only) by the course's own workspace.
 */
class SharedCourseController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->limit(100, '')->value();

        $courses = Course::withoutGlobalScope(TenantScope::class)
            ->with(['tenant', 'sharedWorkspaces'])
            ->withCount(['lessons' => fn ($query) => $query->withoutGlobalScope(TenantScope::class)])
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($request->query('filter') === 'shared', fn ($query) => $query->where(fn ($shared) => $shared->where('is_public', true)->orHas('sharedWorkspaces')))
            ->orderBy('title')
            ->paginate(25)
            ->withQueryString();

        return view('admin.shared-courses.index', [
            'courses' => $courses,
            'search' => $search,
            'filter' => $request->query('filter') === 'shared' ? 'shared' : 'all',
            'workspaces' => Tenant::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request, int $course): RedirectResponse
    {
        $course = Course::withoutGlobalScope(TenantScope::class)->findOrFail($course);

        $validated = $request->validate([
            'workspace_ids' => ['nullable', 'array'],
            'workspace_ids.*' => ['integer', Rule::exists('tenants', 'id')],
        ]);

        $workspaceIds = collect($validated['workspace_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $course->tenant_id)
            ->unique()
            ->values();

        $course->forceFill(['is_public' => $request->boolean('is_public')])->save();
        $course->sharedWorkspaces()->sync($workspaceIds->all());

        ActivityLog::record(
            'courses.shared',
            "Set sharing for {$course->title}: ".($course->is_public ? 'open to every workspace' : $workspaceIds->count().' '.str('workspace')->plural($workspaceIds->count())),
            $course,
            tenantId: $course->tenant_id,
        );

        return back()->with('status', "Sharing updated for {$course->title}.");
    }
}
