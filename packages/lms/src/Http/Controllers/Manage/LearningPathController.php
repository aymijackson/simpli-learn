<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Notifications\PathAssigned;
use App\Support\SafeNotifier;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\LearningPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function index(): View
    {
        return view('lms::manage.paths.index', [
            'paths' => LearningPath::withCount('courses')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('lms::manage.paths.create', [
            'courses' => Course::orderBy('title')->get(['id', 'title', 'is_published']),
            'chosen' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$attributes, $courseIds] = $this->validated($request);

        $path = DB::transaction(function () use ($attributes, $courseIds) {
            $path = LearningPath::create($attributes);
            $path->syncCourses($courseIds);

            return $path;
        });

        ActivityLog::record('paths.created', "Created the learning path {$path->title}", $path);

        return redirect()->route('lms.manage.paths.edit', $path)->with('status', 'Learning path created.');
    }

    public function edit(string $tenant, LearningPath $path): View
    {
        $path->load('courses');
        $people = app(Tenancy::class)->current()->users()->active()->orderBy('name')->get();

        return view('lms::manage.paths.edit', [
            'path' => $path,
            'courses' => Course::orderBy('title')->get(['id', 'title', 'is_published']),
            'chosen' => $path->courses->pluck('id'),
            'people' => $people,
            'progress' => $this->progress($path, $people),
        ]);
    }

    public function update(Request $request, string $tenant, LearningPath $path): RedirectResponse
    {
        [$attributes, $courseIds] = $this->validated($request, $path);

        DB::transaction(function () use ($path, $attributes, $courseIds) {
            $path->update($attributes);
            $path->syncCourses($courseIds);
        });

        return redirect()->route('lms.manage.paths.edit', $path)->with('status', 'Learning path updated.');
    }

    public function destroy(string $tenant, LearningPath $path): RedirectResponse
    {
        $path->delete();
        ActivityLog::record('paths.deleted', "Deleted the learning path {$path->title}");

        return redirect()->route('lms.manage.paths.index')->with('status', 'Learning path deleted. Its courses are unchanged.');
    }

    /** Assign every course in the path, with one email per person. */
    public function assign(Request $request, string $tenant, LearningPath $path): RedirectResponse
    {
        $validated = $request->validate([
            'who' => ['required', Rule::in(['everyone', 'selected'])],
            'user_ids' => ['required_if:who,selected', 'array'],
            'user_ids.*' => ['integer'],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'user_ids.required_if' => 'Choose at least one person, or assign it to everyone.',
            'due_at.after_or_equal' => 'The due date can\'t be in the past.',
        ]);

        $path->load('courses');
        abort_if($path->courses->isEmpty(), 422, 'Add courses to the path first.');

        $people = app(Tenancy::class)->current()->users()->active()->orderBy('name')->get();
        if ($validated['who'] === 'selected') {
            $people = $people->whereIn('id', $validated['user_ids']);
        }
        $dueAt = isset($validated['due_at']) ? Carbon::parse($validated['due_at'])->endOfDay() : null;

        foreach ($path->courses as $course) {
            CourseReportController::assignTo($course, $people, $dueAt, $request->user(), notify: false);
        }
        SafeNotifier::send($people->values(), new PathAssigned($path, app(Tenancy::class)->current(), $request->user()->name, $dueAt));

        ActivityLog::record(
            'paths.assigned',
            "Assigned the learning path {$path->title} to {$people->count()} ".str('person')->plural($people->count()).($dueAt ? ', due '.$dueAt->format('j M Y') : ''),
            $path,
        );

        return redirect()->route('lms.manage.paths.edit', $path)
            ->with('status', "Assigned to {$people->count()} ".str('person')->plural($people->count()).'. They have been enrolled in every course and emailed.');
    }

    /**
     * Everyone who has started any course in the path, with how many they've passed.
     *
     * @return \Illuminate\Support\Collection<int, array{user: \App\Models\User, passed: int, total: int, percent: int}>
     */
    private function progress(LearningPath $path, $people)
    {
        $courseIds = $path->courses->pluck('id');
        $starters = DB::table('enrollments')->whereIn('course_id', $courseIds)->distinct()->pluck('user_id');

        return $people->whereIn('id', $starters)
            ->map(function ($person) use ($path) {
                $steps = $path->stepsFor($person);

                return [
                    'user' => $person,
                    'passed' => $steps->where('status', 'passed')->count(),
                    'total' => $steps->count(),
                    'percent' => LearningPath::percentOf($steps),
                ];
            })
            ->sortByDesc('percent')
            ->values();
    }

    /** @return array{0: array<string, mixed>, 1: array<int, int>} */
    private function validated(Request $request, ?LearningPath $path = null): array
    {
        $tenantId = app(Tenancy::class)->id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('learning_paths', 'slug')->where('tenant_id', $tenantId)->ignore($path)],
            'description' => ['nullable', 'string', 'max:5000'],
            'course_ids' => ['required', 'array', 'min:1', 'max:30'],
            'course_ids.*' => ['integer', Rule::exists('courses', 'id')->where('tenant_id', $tenantId)],
            'positions' => ['nullable', 'array'],
            'positions.*' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'course_ids.required' => 'Pick at least one course for the path.',
        ]);

        // Order the chosen courses by the position typed next to each, then title order.
        $positions = $validated['positions'] ?? [];
        $courseIds = collect($validated['course_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sortBy(fn ($id) => [(int) ($positions[$id] ?? 999), $id])
            ->values()
            ->all();

        return [[
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'is_sequential' => $request->boolean('is_sequential'),
        ], $courseIds];
    }
}
