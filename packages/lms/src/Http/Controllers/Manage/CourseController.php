<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Enums\CourseCertificatePolicy;
use Elibrary\Lms\Enums\CoursePricingPolicy;
use Elibrary\Lms\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);

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
