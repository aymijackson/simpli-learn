<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Lms\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::query()
            ->where('is_published', true)
            ->withCount('lessons')
            ->get();

        return view('lms::courses.index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, string $tenant, Course $course): View
    {
        $course->load('lessons');

        return view('lms::courses.show', [
            'course' => $course,
            'isEnrolled' => $course->isEnrolled($request->user()),
            'progress' => $course->progressPercentFor($request->user()),
            'isPassed' => $course->isPassedBy($request->user()),
        ]);
    }

    public function enroll(Request $request, string $tenant, Course $course): RedirectResponse
    {
        $course->enrollments()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['enrolled_at' => now()],
        );

        return redirect()
            ->route('lms.courses.show', $course)
            ->with('status', "You're enrolled in {$course->title}.");
    }
}
