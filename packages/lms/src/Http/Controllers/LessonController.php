<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Achievements;
use Elibrary\Lms\Certificates\CourseCertificateService;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Elibrary\Lms\Support\VideoEmbed;
use Illuminate\Http\JsonResponse;
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
            'video' => $lesson->video(),
            'videoPercent' => $lesson->video() ? $lesson->videoPercentFor($user) : 0,
            'mustWatch' => $lesson->mustWatchVideo($user),
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

        if ($lesson->mustWatchVideo($request->user())) {
            return redirect()->route('lms.lessons.show', [$course, $lesson])
                ->with('error', 'Watch the video to complete this lesson.');
        }

        $this->markComplete($course, $lesson, $request->user());

        return redirect()
            ->route('lms.lessons.show', [$course, $lesson])
            ->with('status', 'Marked as complete.');
    }

    /**
     * The player reports how much of the video has actually been watched
     * (seconds played, not how far it was dragged). Crossing the threshold
     * completes the lesson.
     */
    public function videoProgress(Request $request, string $tenant, Course $course, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->course_id === $course->id && $lesson->video(), 404);
        abort_unless($course->isEnrolled($request->user()), 403);
        abort_unless($lesson->isUnlockedFor($request->user()), 403);

        $percent = (int) $request->validate(['percent' => ['required', 'integer', 'min:0', 'max:100']])['percent'];
        $progress = $lesson->progress()->firstOrNew(['user_id' => $request->user()->id]);
        $progress->video_percent = max((int) $progress->video_percent, $percent);
        $progress->save();

        $completed = $progress->completed_at !== null;
        if (! $completed && $progress->video_percent >= VideoEmbed::WATCHED_TO_COMPLETE) {
            $this->markComplete($course, $lesson, $request->user());
            $completed = true;
        }

        return response()->json(['percent' => $progress->video_percent, 'completed' => $completed]);
    }

    private function markComplete(Course $course, Lesson $lesson, $user): void
    {
        $lesson->progress()->updateOrCreate(['user_id' => $user->id], ['completed_at' => now()]);

        if ($course->isEnrolled($user)) {
            app(CourseCertificateService::class)->issueIfPassedAndFree($course, $user);
            Achievements::recordActivity($user);
            Achievements::evaluate($user);
        }
    }
}
