<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\CourseAssigned;
use App\Support\SafeNotifier;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseAssignment;
use Elibrary\Lms\Reports\CourseProgressReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Per-course completion report for owners, with assigning and CSV export.
 */
class CourseReportController extends Controller
{
    public function show(Request $request, string $tenant, Course $course): View
    {
        $rows = CourseProgressReport::build($course, $this->people());
        $filter = $request->string('status')->value();

        $counts = [
            'all' => $rows->count(),
            'overdue' => $rows->where('overdue', true)->count(),
        ] + collect(CourseProgressReport::LABELS)->mapWithKeys(fn ($label, $status) => [$status => $rows->where('status', $status)->count()])->all();

        $visible = match (true) {
            $filter === 'overdue' => $rows->where('overdue', true),
            array_key_exists($filter, CourseProgressReport::LABELS) => $rows->where('status', $filter),
            default => $rows,
        };

        return view('lms::manage.courses.report', [
            'course' => $course,
            'rows' => $visible->sortBy(fn ($row) => [$row['overdue'] ? 0 : 1, array_search($row['status'], array_keys(CourseProgressReport::LABELS)), $row['user']->name])->values(),
            'allRows' => $rows,
            'counts' => $counts,
            'activeFilter' => $filter === 'overdue' || array_key_exists($filter, CourseProgressReport::LABELS) ? $filter : 'all',
            'completionRate' => $rows->count() ? (int) round($counts[CourseProgressReport::COMPLETED] / $rows->count() * 100) : 0,
        ]);
    }

    public function export(string $tenant, Course $course): StreamedResponse
    {
        $rows = CourseProgressReport::build($course, $this->people());
        ActivityLog::record('reports.exported', "Downloaded the completion report for {$course->title}", $course);

        return response()->streamDownload(function () use ($rows, $course) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Course', 'Name', 'Email', 'Role', 'Status', 'Progress %', 'Enrolled', 'Due', 'Overdue', 'Completed', 'Last activity']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $course->title,
                    $row['user']->name,
                    $row['user']->email,
                    $row['user']->role->label(),
                    CourseProgressReport::LABELS[$row['status']],
                    $row['progress'],
                    $row['enrolled_at']?->format('Y-m-d'),
                    $row['assignment']?->due_at?->format('Y-m-d'),
                    $row['overdue'] ? 'Yes' : 'No',
                    $row['completed_at']?->format('Y-m-d H:i'),
                    $row['last_activity_at']?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'completion-'.$course->slug.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function assign(Request $request, string $tenant, Course $course): RedirectResponse
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

        $people = $this->people();
        if ($validated['who'] === 'selected') {
            $people = $people->whereIn('id', $validated['user_ids']);
        }

        $dueAt = isset($validated['due_at']) ? Carbon::parse($validated['due_at'])->endOfDay() : null;
        $assigned = self::assignTo($course, $people, $dueAt, $request->user());

        ActivityLog::record(
            'courses.assigned',
            "Assigned {$course->title} to ".$assigned.' '.str('person')->plural($assigned).($dueAt ? ', due '.$dueAt->format('j M Y') : ''),
            $course,
        );

        return redirect()->route('lms.manage.courses.report', $course)
            ->with('status', "Assigned to {$assigned} ".str('person')->plural($assigned).'. They have been enrolled and emailed.');
    }

    public function unassign(string $tenant, Course $course, CourseAssignment $assignment): RedirectResponse
    {
        abort_unless($assignment->course_id === $course->id, 404);

        $name = $assignment->user?->name ?? 'someone';
        $assignment->delete();
        ActivityLog::record('courses.unassigned', "Removed the {$course->title} assignment for {$name}", $course);

        return back()->with('status', "Assignment removed for {$name}. They stay enrolled and keep their progress.");
    }

    /**
     * Assign (or update the due date of) a course for each person: enrolls
     * them and emails them. Returns how many people were assigned.
     *
     * @param  Collection<int, User>  $people
     */
    public static function assignTo(Course $course, Collection $people, ?Carbon $dueAt, User $by): int
    {
        $workspace = app(Tenancy::class)->current();

        foreach ($people as $person) {
            $assignment = CourseAssignment::updateOrCreate(
                ['course_id' => $course->id, 'user_id' => $person->id],
                ['tenant_id' => $workspace->id, 'assigned_by_user_id' => $by->id, 'due_at' => $dueAt, 'reminded_at' => null],
            );
            $course->enrollments()->firstOrCreate(['user_id' => $person->id], ['tenant_id' => $workspace->id, 'enrolled_at' => now()]);

            if ($assignment->wasRecentlyCreated || $assignment->wasChanged('due_at')) {
                SafeNotifier::send($person, new CourseAssigned($assignment->setRelation('course', $course), $workspace, $by->name));
            }
        }

        return $people->count();
    }

    /** Everyone active in the workspace. */
    private function people(): Collection
    {
        return app(Tenancy::class)->current()->users()->active()->orderBy('name')->get();
    }
}
