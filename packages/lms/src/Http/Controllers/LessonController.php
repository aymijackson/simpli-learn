<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Achievements;
use Elibrary\Lms\Certificates\CourseCertificateService;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Request $request, string $tenant, Course $course, Lesson $lesson): View
    {
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($lesson->isAccessibleTo($request->user()), 403, 'Enroll in this course to view its lessons.');
        abort_unless($lesson->isUnlockedFor($request->user()), 403, 'Pass the required exam to unlock this lesson.');

        $user = $request->user();
        if ($course->isEnrolled($user)) {
            Achievements::recordActivity($user);
        }
        $lessons = $course->lessons;
        $lessons->each->setRelation('course', $course);
        $index = $lessons->search(fn ($l) => $l->id === $lesson->id);
        $next = $index < $lessons->count() - 1 ? $lessons[$index + 1] : null;

        // Quizzes sit after the lesson they follow; the outline shows them in place.
        $checkpoints = $course->isEnrolled($user)
            ? $course->checkpoints()
                ->filter(fn ($checkpoint) => $checkpoint['after'])
                ->map(fn ($checkpoint) => $checkpoint + ['status' => $course->checkpointStatus($checkpoint, $user)])
                ->keyBy(fn ($checkpoint) => $checkpoint['after']->id)
            : collect();

        return view('lms::lessons.show', [
            'course' => $course,
            'lesson' => $lesson,
            'isCompleted' => $lesson->isCompletedBy($user),
            'previous' => $index > 0 ? $lessons[$index - 1] : null,
            'next' => $next,
            'nextIsOpen' => $next && $next->isAccessibleTo($user) && $next->isUnlockedFor($user),
            'checkpoints' => $checkpoints,
            'checkpoint' => $checkpoints->get($lesson->id),
        ]);
    }

    public function complete(Request $request, string $tenant, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($lesson->isAccessibleTo($request->user()), 403);
        abort_unless($lesson->isUnlockedFor($request->user()), 403, 'Pass the required exam to unlock this lesson.');

        $lesson->progress()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['completed_at' => now()],
        );

        if ($course->isEnrolled($request->user())) {
            app(CourseCertificateService::class)->issueIfPassedAndFree($course, $request->user());
            Achievements::recordActivity($request->user());
            Achievements::evaluate($request->user());
        }

        return redirect()
            ->route('lms.lessons.show', [$course, $lesson])
            ->with('status', 'Marked as complete.');
    }
}
