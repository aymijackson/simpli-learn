<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseAssignment;
use Elibrary\Lms\Models\Enrollment;
use Elibrary\Lms\Reports\CourseProgressReport;
use Illuminate\Http\Request;

/**
 * The learner's "My learning" dashboard: what they're in the middle of,
 * recent results, and things to explore next — only for enabled modules.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, Tenancy $tenancy)
    {
        $tenant = $tenancy->current();
        $user = $request->user();
        $enabledModules = $tenant->tenantModules()->where('is_enabled', true)->get()
            ->sortBy(fn ($tenantModule) => array_search($tenantModule->module, Module::cases(), true))
            ->values();
        $enabled = $enabledModules->map(fn ($tenantModule) => $tenantModule->module);

        $data = [
            'tenant' => $tenant,
            'enabledModules' => $enabledModules,
            'myCourses' => collect(),
            'assignments' => collect(),
            'suggestedCourses' => collect(),
            'recentAttempts' => collect(),
            'openAttempts' => collect(),
            'suggestedExams' => collect(),
            'checkouts' => collect(),
            'newResources' => collect(),
        ];

        if ($enabled->contains(Module::Lms)) {
            $enrolledCourseIds = Enrollment::where('user_id', $user->id)->pluck('course_id');

            $data['myCourses'] = Course::query()
                ->whereIn('id', $enrolledCourseIds)
                ->withCount('lessons')
                ->get()
                ->map(function (Course $course) use ($user) {
                    $course->progress = $course->progressPercentFor($user);

                    return $course;
                })
                ->sortBy(fn (Course $course) => $course->progress === 100 ? 1 : 0)
                ->values();

            // Courses someone has assigned to this person that they haven't finished yet.
            $data['assignments'] = CourseAssignment::where('user_id', $user->id)
                ->with('course')
                ->get()
                ->filter(fn (CourseAssignment $assignment) => $assignment->course !== null)
                ->map(function (CourseAssignment $assignment) use ($user) {
                    $row = CourseProgressReport::build($assignment->course, collect([$user]))->first();
                    $assignment->progress = $row['progress'];
                    $assignment->status = $row['status'];

                    return $assignment;
                })
                ->reject(fn (CourseAssignment $assignment) => $assignment->status === CourseProgressReport::COMPLETED)
                ->sortBy(fn (CourseAssignment $assignment) => $assignment->due_at?->timestamp ?? PHP_INT_MAX)
                ->values();

            $data['suggestedCourses'] = Course::query()
                ->where('is_published', true)
                ->whereNotIn('id', $enrolledCourseIds)
                ->withCount('lessons')
                ->latest()
                ->take(4)
                ->get();
        }

        if ($enabled->contains(Module::Cbt)) {
            $data['recentAttempts'] = ExamAttempt::query()
                ->where('user_id', $user->id)
                ->whereNotNull('submitted_at')
                ->with('exam')
                ->latest('submitted_at')
                ->take(5)
                ->get()
                ->filter(fn (ExamAttempt $attempt) => $attempt->exam !== null);

            $data['openAttempts'] = ExamAttempt::query()
                ->where('user_id', $user->id)
                ->whereNull('submitted_at')
                ->with('exam')
                ->latest('started_at')
                ->get()
                ->filter(fn (ExamAttempt $attempt) => $attempt->exam !== null);

            $data['suggestedExams'] = Exam::query()
                ->where('is_published', true)
                ->withCount('questions')
                ->latest()
                ->take(3)
                ->get();
        }

        if ($enabled->contains(Module::Library)) {
            $data['checkouts'] = LibraryCheckout::query()
                ->where('user_id', $user->id)
                ->whereNull('returned_at')
                ->with('resource')
                ->orderBy('due_at')
                ->get()
                ->filter(fn (LibraryCheckout $checkout) => $checkout->resource !== null);

            $data['newResources'] = LibraryResource::query()
                ->where('is_published', true)
                ->latest()
                ->take(6)
                ->get();
        }

        return view('home', $data);
    }
}
