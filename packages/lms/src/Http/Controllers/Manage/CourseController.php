<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Enums\CourseCertificatePolicy;
use Elibrary\Lms\Enums\CourseLevel;
use Elibrary\Lms\Enums\CoursePricingPolicy;
use Elibrary\Lms\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        return view('lms::manage.courses.index', [
            'courses' => Course::withCount('lessons')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('lms::manage.courses.create', [
            'exams' => Exam::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $course = Course::create($this->validated($request));

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Course created.');
    }

    public function edit(string $tenant, Course $course): View
    {
        return view('lms::manage.courses.edit', [
            'course' => $course,
            'lessons' => $course->lessons,
            'modules' => $course->modules,
            'exams' => Exam::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, string $tenant, Course $course): RedirectResponse
    {
        $course->update($this->validated($request, $course));

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Course updated.');
    }

    public function destroy(string $tenant, Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()->route('lms.manage.courses.index')->with('status', 'Course deleted.');
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        $tenantId = app(Tenancy::class)->id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'alpha_dash', 'max:255',
                Rule::unique('courses', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($course),
            ],
            'description' => ['nullable', 'string'],
            'assessment_mode' => ['nullable', Rule::in(array_column(AssessmentMode::cases(), 'value'))],
            'final_exam_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('assessment_mode') === AssessmentMode::CourseFinal->value),
                Rule::exists('exams', 'id')->where('tenant_id', $tenantId),
            ],
            'pricing_policy' => ['nullable', Rule::in(array_column(CoursePricingPolicy::cases(), 'value'))],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'certificate_policy' => ['nullable', Rule::in(array_column(CourseCertificatePolicy::cases(), 'value'))],
            'certificate_price' => ['nullable', 'numeric', 'min:0'],
            'certificate_currency' => ['nullable', 'string', 'size:3'],
            // Catalog details (all optional).
            'subtitle' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', Rule::in(array_column(CourseLevel::cases(), 'value'))],
            'duration_hours' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'instructor_bio' => ['nullable', 'string', 'max:2000'],
            'outcomes_text' => ['nullable', 'string', 'max:5000'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $validated['duration_minutes'] = isset($validated['duration_hours']) ? (int) round($validated['duration_hours'] * 60) : null;
        $validated['outcomes'] = collect(preg_split('/\r?\n/', (string) ($validated['outcomes_text'] ?? '')))
            ->map(fn ($line) => trim(ltrim(trim($line), '-*•')))
            ->filter()
            ->take(12)
            ->values()
            ->all() ?: null;
        $validated['category'] = isset($validated['category']) ? trim($validated['category']) : null;

        if ($request->hasFile('cover_image') || $request->boolean('remove_cover')) {
            if ($course?->cover_image_path) {
                Storage::disk('public')->delete($course->cover_image_path);
            }
            $validated['cover_image_path'] = $request->hasFile('cover_image')
                ? $request->file('cover_image')->storeAs(
                    'course-covers/'.$tenantId,
                    Str::random(40).'.'.$request->file('cover_image')->extension(),
                    'public',
                )
                : null;
        }

        unset($validated['duration_hours'], $validated['outcomes_text'], $validated['cover_image']);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['assessment_mode'] = $validated['assessment_mode'] ?? AssessmentMode::None->value;

        if ($validated['assessment_mode'] !== AssessmentMode::CourseFinal->value) {
            $validated['final_exam_id'] = null;
        }

        $validated['pricing_policy'] = $validated['pricing_policy'] ?? CoursePricingPolicy::Free->value;
        if ($validated['pricing_policy'] === CoursePricingPolicy::Free->value) {
            $validated['price'] = null;
            $validated['currency'] = null;
        }

        $validated['certificate_policy'] = $validated['certificate_policy'] ?? CourseCertificatePolicy::None->value;
        if ($validated['certificate_policy'] !== CourseCertificatePolicy::Paid->value) {
            $validated['certificate_price'] = null;
            $validated['certificate_currency'] = null;
        }

        return $validated;
    }
}
