<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseModuleController extends Controller
{
    public function store(Request $request, string $tenant, Course $course): RedirectResponse
    {
        $course->modules()->create($this->validated($request));

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Module added.');
    }

    public function update(Request $request, string $tenant, Course $course, CourseModule $module): RedirectResponse
    {
        abort_unless($module->course_id === $course->id, 404);

        $module->update($this->validated($request));

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Module updated.');
    }

    public function destroy(string $tenant, Course $course, CourseModule $module): RedirectResponse
    {
        abort_unless($module->course_id === $course->id, 404);

        $module->delete();

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Module deleted.');
    }

    private function validated(Request $request): array
    {
        $tenantId = app(Tenancy::class)->id();

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:0'],
            'exam_id' => ['nullable', Rule::exists('exams', 'id')->where('tenant_id', $tenantId)],
        ]);
    }
}
