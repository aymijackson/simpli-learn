<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Support\JobTitleSync;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Notifications\InstructorAssigned;
use App\Support\SafeNotifier;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Enums\CourseCertificatePolicy;
use Elibrary\Lms\Enums\CourseLevel;
use Elibrary\Lms\Enums\CoursePricingPolicy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Support\SharedCourses;
use Elibrary\Lms\Support\Teaching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = app(Tenancy::class)->id();
        $isOwner = $request->user()->isOwner();

        return view('lms::manage.courses.index', [
            'isOwner' => $isOwner,
            // Instructors see just the courses they teach.
            'courses' => Course::withCount('lessons')
                ->when(! $isOwner, fn ($query) => $query->whereIn('id', Teaching::courseIds($request->user())))
                ->latest()
                ->get(),
            // Read directly (the back office is strict): published courses
            // other workspaces open to all or share with this one.
            'sharedCourses' => ! $isOwner ? collect() : Course::withoutGlobalScope(TenantScope::class)
                ->whereIn('id', SharedCourses::ids($tenantId))
                ->with('tenant')
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('lms::manage.courses.create', [
            'exams' => Exam::orderBy('title')->get(),
            'otherCourses' => Course::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $course = Course::create($this->validated($request));
        $this->syncPrerequisites($request, $course);
        JobTitleSync::apply($request, $course->jobTitles());

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Course created.');
    }

    public function edit(Request $request, string $tenant, Course $course): View
    {
        Teaching::authorize($course);

        return view('lms::manage.courses.edit', [
            'course' => $course,
            'isOwner' => $request->user()->isOwner(),
            'members' => $request->user()->isOwner() ? app(Tenancy::class)->current()->users()->active()->orderBy('name')->get() : collect(),
            'instructorIds' => $course->instructors()->pluck('users.id'),
            'lessons' => $course->lessons,
            'modules' => $course->modules,
            'exams' => Exam::orderBy('title')->get(),
            'otherCourses' => Course::whereKeyNot($course->id)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, string $tenant, Course $course): RedirectResponse
    {
        $course->update($this->validated($request, $course));
        $this->syncPrerequisites($request, $course);
        JobTitleSync::apply($request, $course->jobTitles());

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Course updated.');
    }

    /** Owners choose who teaches the course. */
    public function instructors(Request $request, string $tenant, Course $course): RedirectResponse
    {
        $workspace = app(Tenancy::class)->current();
        $validated = $request->validate([
            'instructor_ids' => ['nullable', 'array'],
            'instructor_ids.*' => ['integer', Rule::exists('users', 'id')->where('tenant_id', $workspace->id)->whereNull('deactivated_at')],
        ]);

        $ids = collect($validated['instructor_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $before = $course->instructors()->pluck('users.id');
        $course->instructors()->sync($ids->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $workspace->id]])->all());

        $added = User::whereIn('id', $ids->diff($before))->get();
        foreach ($added as $person) {
            SafeNotifier::send($person, new InstructorAssigned($course, $workspace, $request->user()->name));
        }

        ActivityLog::record('courses.instructors', "Set the instructors for {$course->title}: ".(User::whereIn('id', $ids)->pluck('name')->join(', ') ?: 'none'), $course);

        return redirect()->route('lms.manage.courses.edit', $course)->with('status', 'Instructors updated.');
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
            'prerequisite_ids' => ['nullable', 'array', 'max:20'],
            'prerequisite_ids.*' => ['integer', Rule::exists('courses', 'id')->where('tenant_id', $tenantId), Rule::notIn(array_filter([$course?->id]))],
        ]);

        if ($course) {
            $loops = Course::whereIn('id', $validated['prerequisite_ids'] ?? [])->get()
                ->filter(fn (Course $candidate) => $course->wouldCreatePrerequisiteLoop($candidate));
            if ($loops->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'prerequisite_ids' => '"'.$loops->first()->title.'" already requires this course, so it can\'t also come before it.',
                ]);
            }
        }

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

        unset($validated['duration_hours'], $validated['outcomes_text'], $validated['cover_image'], $validated['prerequisite_ids']);
        $validated['sequential_lessons'] = $request->boolean('sequential_lessons');

        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_public'] = $request->boolean('is_public');
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

    private function syncPrerequisites(Request $request, Course $course): void
    {
        $tenantId = app(Tenancy::class)->id();

        $chosen = collect($request->input('prerequisite_ids', []))
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $course->id)
            ->unique();

        // Only the owner's own choices live here; a sequential learning path
        // manages the links it created (learning_path_id set) by itself.
        DB::table('course_prerequisites')
            ->where('course_id', $course->id)
            ->whereNull('learning_path_id')
            ->whereNotIn('prerequisite_course_id', $chosen->all())
            ->delete();

        foreach ($chosen as $id) {
            DB::table('course_prerequisites')->updateOrInsert(
                ['course_id' => $course->id, 'prerequisite_course_id' => $id],
                ['tenant_id' => $tenantId, 'learning_path_id' => null, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }
}
