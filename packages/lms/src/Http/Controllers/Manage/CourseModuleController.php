<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseModule;
use Elibrary\Lms\Support\Teaching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseModuleController extends Controller
{
    public function store(Request $request, string $tenant, Course $course): RedirectResponse
    {
        Teaching::authorize($course);
        $course->modules()->create($this->validated($request));

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Module added.');
    }

    public function update(Request $request, string $tenant, Course $course, CourseModule $module): RedirectResponse
    {
        Teaching::authorize($course);
        abort_unless($module->course_id === $course->id, 404);

        $module->update($this->validated($request));

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Module updated.');
    }

    public function destroy(string $tenant, Course $course, CourseModule $module): RedirectResponse
    {
        Teaching::authorize($course);
        abort_unless($module->course_id === $course->id, 404);

        $module->delete();

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Module deleted.');
    }

    private function validated(Request $request): array
    {
        $tenantId = app(Tenancy::class)->id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:0'],
            'exam_id' => ['nullable', Rule::exists('exams', 'id')->where('tenant_id', $tenantId)],
        ]);

        if (! $request->user()->isOwner()) {
            unset($validated['exam_id']); // which exam gates a module is the owner's call
        }

        return $validated;
    }
}
