<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Elibrary\Lms\Support\VideoEmbed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function create(string $tenant, Course $course): View
    {
        return view('lms::manage.lessons.create', [
            'course' => $course,
            'nextPosition' => $course->lessons()->count(),
            'modules' => $course->modules,
            'exams' => Exam::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request, string $tenant, Course $course): RedirectResponse
    {
        $course->lessons()->create($this->validated($request, $course));

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Lesson added.');
    }

    public function edit(string $tenant, Course $course, Lesson $lesson): View
    {
        abort_unless($lesson->course_id === $course->id, 404);

        return view('lms::manage.lessons.edit', [
            'course' => $course,
            'lesson' => $lesson,
            'modules' => $course->modules,
            'exams' => Exam::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, string $tenant, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $lesson->update($this->validated($request, $course));

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Lesson updated.');
    }

    public function destroy(string $tenant, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $lesson->delete();

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Lesson deleted.');
    }

    private function validated(Request $request, Course $course): array
    {
        $tenantId = app(Tenancy::class)->id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required_without:video_url', 'nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                if ($value && ! VideoEmbed::parse($value)) {
                    $fail('Paste a YouTube, Vimeo or Bunny Stream video link.');
                }
            }],
            'position' => ['required', 'integer', 'min:0'],
            'course_module_id' => [
                'nullable',
                Rule::exists('course_modules', 'id')->where('tenant_id', $tenantId)->where('course_id', $course->id),
            ],
            'exam_id' => ['nullable', Rule::exists('exams', 'id')->where('tenant_id', $tenantId)],
        ]);

        $validated['is_preview'] = $request->boolean('is_preview');
        $validated['require_video_watch'] = $request->boolean('require_video_watch');
        $validated['content'] = $validated['content'] ?? '';

        return $validated;
    }
}
