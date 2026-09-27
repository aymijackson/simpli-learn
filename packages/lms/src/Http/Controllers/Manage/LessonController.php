<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Elibrary\Lms\Support\ScormPackage;
use Elibrary\Lms\Support\Teaching;
use Elibrary\Lms\Support\VideoEmbed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function create(string $tenant, Course $course): View
    {
        Teaching::authorize($course);
        return view('lms::manage.lessons.create', [
            'course' => $course,
            'nextPosition' => $course->lessons()->count(),
            'modules' => $course->modules,
            'exams' => Exam::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request, string $tenant, Course $course): RedirectResponse
    {
        Teaching::authorize($course);
        $lesson = $course->lessons()->create($this->validated($request, $course));
        $this->saveScorm($request, $lesson);

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Lesson added.');
    }

    public function edit(string $tenant, Course $course, Lesson $lesson): View
    {
        Teaching::authorize($course);
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
        Teaching::authorize($course);
        abort_unless($lesson->course_id === $course->id, 404);

        $lesson->update($this->validated($request, $course, $lesson));
        $this->saveScorm($request, $lesson);

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Lesson updated.');
    }

    public function destroy(string $tenant, Course $course, Lesson $lesson): RedirectResponse
    {
        Teaching::authorize($course);
        abort_unless($lesson->course_id === $course->id, 404);

        ScormPackage::delete($lesson->scorm_path);
        $lesson->delete();

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Lesson deleted.');
    }

    /** A new package replaces the old one; "remove" takes it away. */
    private function saveScorm(Request $request, Lesson $lesson): void
    {
        if ($request->hasFile('scorm_package')) {
            $package = ScormPackage::import($request->file('scorm_package'), $lesson->tenant_id);
            ScormPackage::delete($lesson->scorm_path);
            $lesson->forceFill(['scorm_path' => $package['path'], 'scorm_launch' => $package['launch'], 'scorm_version' => $package['version']])->save();
        } elseif ($request->boolean('remove_scorm') && $lesson->scorm_path) {
            ScormPackage::delete($lesson->scorm_path);
            $lesson->forceFill(['scorm_path' => null, 'scorm_launch' => null, 'scorm_version' => null])->save();
        }
    }

    private function validated(Request $request, Course $course, ?Lesson $lesson = null): array
    {
        $tenantId = app(Tenancy::class)->id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            // Notes are optional when the lesson is a video or a SCORM package.
            'content' => [($lesson?->scorm_path && ! $request->boolean('remove_scorm')) ? 'nullable' : 'required_without_all:video_url,scorm_package', 'nullable', 'string'],
            'scorm_package' => ['nullable', 'file', 'mimes:zip', 'max:512000'],
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
        if (! $request->user()->isOwner()) {
            unset($validated['exam_id']); // which exam gates a lesson is the owner's call
        }
        $validated['require_video_watch'] = $request->boolean('require_video_watch');
        $validated['scorm_required'] = $request->boolean('scorm_required');
        unset($validated['scorm_package']);
        $validated['content'] = $validated['content'] ?? '';

        return $validated;
    }
}
