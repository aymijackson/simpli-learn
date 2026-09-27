<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\User;
use App\Support\Invitations;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Certificate;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Lms\Http\Controllers\Manage\CourseReportController;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseCertificate;
use Elibrary\Lms\Reports\CourseProgressReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Version 1 of the workspace API. Everything is scoped to the API key's
 * workspace (see AuthenticateApiToken). Lists are paginated: ?page=, and
 * ?per_page= up to 100.
 */
class ApiController extends Controller
{
    public function people(Request $request): JsonResponse
    {
        $people = app(Tenancy::class)->current()->users()
            ->when($request->filled('email'), fn ($query) => $query->where('email', $request->string('email')->lower()->value()))
            ->orderBy('id')
            ->paginate($this->perPage($request));

        return $this->page($people, fn (User $user) => $this->person($user));
    }

    /** Add someone to the workspace; they're emailed an invitation to set a password. */
    public function addPerson(Request $request): JsonResponse
    {
        $tenant = app(Tenancy::class)->current();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where('tenant_id', $tenant->id)],
            'role' => ['nullable', Rule::in([UserRole::Member->value])], // owners are added in the app, not by API
        ]);

        $person = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'role' => UserRole::Member,
            'password' => Hash::make(Str::random(64)),
        ]);

        $token = $this->token($request);
        ActivityLog::record('team.member_added', "Added {$person->name} ({$person->email}) via the API key \"{$token->name}\"", $person, actor: $token->createdBy);
        $invited = Invitations::send($person, $tenant, $token->createdBy?->name ?? $tenant->name);

        return response()->json(['data' => $this->person($person) + ['invitation_sent' => $invited]], 201);
    }

    public function courses(Request $request): JsonResponse
    {
        $courses = Course::withCount('lessons')->orderBy('id')->paginate($this->perPage($request));

        return $this->page($courses, fn (Course $course) => [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'published' => $course->is_published,
            'lessons' => $course->lessons_count,
            'url' => route('lms.courses.show', ['tenant' => app(Tenancy::class)->current()->slug, 'course' => $course->slug]),
            'created_at' => $course->created_at?->toIso8601String(),
        ]);
    }

    /** Where everyone in the workspace is on a course. */
    public function courseProgress(Request $request, int $course): JsonResponse
    {
        $course = Course::findOrFail($course);
        $people = app(Tenancy::class)->current()->users()->active()->orderBy('id')->get();
        $rows = CourseProgressReport::build($course, $people)
            ->when($request->filled('status'), fn ($rows) => $rows->where('status', $request->string('status')->value()));

        return response()->json(['data' => $rows->values()->map(fn ($row) => [
            'person' => $this->person($row['user']),
            'status' => $row['status'],
            'progress' => $row['progress'],
            'enrolled_at' => $row['enrolled_at']?->toIso8601String(),
            'completed_at' => $row['completed_at'] ? Carbon::parse($row['completed_at'])->toIso8601String() : null,
            'due_at' => $row['assignment']?->due_at?->toIso8601String(),
            'overdue' => (bool) $row['overdue'],
        ])]);
    }

    /** Assign a course to people (by email), enrolling them and emailing them as in the app. */
    public function assignCourse(Request $request, int $course): JsonResponse
    {
        $course = Course::findOrFail($course);
        $validated = $request->validate([
            'emails' => ['required', 'array', 'min:1', 'max:500'],
            'emails.*' => ['email'],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $emails = collect($validated['emails'])->map(fn ($email) => Str::lower($email))->unique();
        $people = app(Tenancy::class)->current()->users()->active()->whereIn('email', $emails)->get();
        $token = $this->token($request);
        $dueAt = isset($validated['due_at']) ? Carbon::parse($validated['due_at'])->endOfDay() : null;

        $by = $token->createdBy ?? app(Tenancy::class)->current()->users()->where('role', UserRole::Owner)->first();
        CourseReportController::assignTo($course, $people, $dueAt, $by);
        ActivityLog::record('courses.assigned', "Assigned {$course->title} to {$people->count()} ".Str::plural('person', $people->count())." via the API key \"{$token->name}\"", $course, actor: $token->createdBy);

        return response()->json(['data' => [
            'assigned' => $people->pluck('email')->values(),
            'not_found' => $emails->diff($people->pluck('email'))->values(),
            'due_at' => $dueAt?->toIso8601String(),
        ]]);
    }

    public function exams(Request $request): JsonResponse
    {
        $exams = Exam::withCount('questions')->orderBy('id')->paginate($this->perPage($request));

        return $this->page($exams, fn (Exam $exam) => [
            'id' => $exam->id,
            'title' => $exam->title,
            'slug' => $exam->slug,
            'published' => $exam->is_published,
            'questions' => $exam->questions_count,
            'pass_percentage' => $exam->pass_percentage,
        ]);
    }

    /** Submitted attempts, newest first; ?since= limits to attempts submitted after a date. */
    public function examResults(Request $request, int $exam): JsonResponse
    {
        $exam = Exam::findOrFail($exam);
        $attempts = ExamAttempt::where('exam_id', $exam->id)
            ->whereNotNull('submitted_at')
            ->when($request->filled('since'), fn ($query) => $query->where('submitted_at', '>=', Carbon::parse($request->string('since')->value())))
            ->with('user')
            ->latest('submitted_at')
            ->paginate($this->perPage($request));

        return $this->page($attempts, fn (ExamAttempt $attempt) => [
            'id' => $attempt->id,
            'person' => $attempt->user ? $this->person($attempt->user) : null,
            'score' => $attempt->score,
            'passed' => $attempt->passed(),
            'awaiting_marking' => $attempt->isAwaitingMarking(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
        ]);
    }

    /** Course and exam certificates, newest first; ?since= for "what's new". */
    public function certificates(Request $request): JsonResponse
    {
        $request->validate(['since' => ['nullable', 'date']]);
        $since = $request->filled('since') ? Carbon::parse($request->string('since')->value()) : null;
        $limit = $this->perPage($request);

        $course = CourseCertificate::with(['user', 'course'])
            ->when($since, fn ($query) => $query->where('issued_at', '>=', $since))
            ->latest('issued_at')->limit($limit)->get()
            ->map(fn ($certificate) => [
                'type' => 'course',
                'title' => $certificate->course?->title,
                'person' => $certificate->user ? $this->person($certificate->user) : null,
                'issued_at' => $certificate->issued_at?->toIso8601String(),
                'verify_url' => route('course-certificates.verify', $certificate->verification_token),
            ]);
        $exam = Certificate::with(['user', 'exam'])
            ->when($since, fn ($query) => $query->where('issued_at', '>=', $since))
            ->latest('issued_at')->limit($limit)->get()
            ->map(fn ($certificate) => [
                'type' => 'exam',
                'title' => $certificate->exam?->title,
                'person' => $certificate->user ? $this->person($certificate->user) : null,
                'issued_at' => $certificate->issued_at?->toIso8601String(),
                'verify_url' => route('certificates.verify', $certificate->verification_token),
            ]);

        return response()->json(['data' => $course->concat($exam)->sortByDesc('issued_at')->take($limit)->values()]);
    }

    /** @return array<string, mixed> */
    private function person(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'active' => $user->deactivated_at === null,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    private function page(LengthAwarePaginator $page, callable $map): JsonResponse
    {
        return response()->json([
            'data' => collect($page->items())->map($map)->values(),
            'meta' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()],
        ]);
    }

    private function perPage(Request $request): int
    {
        return max(1, min(100, $request->integer('per_page', 50)));
    }

    private function token(Request $request): ApiToken
    {
        return $request->attributes->get('api_token');
    }
}
