<?php

namespace Elibrary\Lms\Reports;

use App\Models\User;
use Carbon\CarbonInterface;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseAssignment;
use Elibrary\Lms\Models\Enrollment;
use Elibrary\Lms\Models\LessonProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Who has finished a course, who is part-way through and who hasn't started,
 * for a whole team at once. Uses a handful of grouped queries rather than
 * one progress lookup per person, so it stays fast for large teams.
 */
class CourseProgressReport
{
    public const COMPLETED = 'completed';

    public const FINAL_PENDING = 'final_pending';

    public const IN_PROGRESS = 'in_progress';

    public const NOT_STARTED = 'not_started';

    public const NOT_ENROLLED = 'not_enrolled';

    public const LABELS = [
        self::COMPLETED => 'Completed',
        self::FINAL_PENDING => 'Final exam pending',
        self::IN_PROGRESS => 'In progress',
        self::NOT_STARTED => 'Not started',
        self::NOT_ENROLLED => 'Not enrolled',
    ];

    /**
     * @param  Collection<int, User>  $people
     * @return Collection<int, array{user: User, status: string, progress: int, enrolled_at: ?CarbonInterface, completed_at: ?CarbonInterface, last_activity_at: ?CarbonInterface, assignment: ?CourseAssignment, overdue: bool}>
     */
    public static function build(Course $course, Collection $people): Collection
    {
        $userIds = $people->pluck('id');
        $lessonIds = $course->lessons()->pluck('id');
        $totalLessons = $lessonIds->count();

        $enrollments = Enrollment::where('course_id', $course->id)->whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $assignments = CourseAssignment::where('course_id', $course->id)->whereIn('user_id', $userIds)->get()->keyBy('user_id');

        $lessonStats = LessonProgress::query()
            ->whereIn('lesson_id', $lessonIds)
            ->whereIn('user_id', $userIds)
            ->whereNotNull('completed_at')
            ->selectRaw('user_id, COUNT(*) as completed_lessons, MAX(completed_at) as last_completed_at')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // For "pass the final exam" courses: when each person first passed it.
        $finalPasses = collect();
        if ($course->assessment_mode === AssessmentMode::CourseFinal && $course->finalExam) {
            $finalPasses = ExamAttempt::query()
                ->where('exam_id', $course->final_exam_id)
                ->whereIn('user_id', $userIds)
                ->whereNotNull('submitted_at')
                ->where('score', '>=', $course->finalExam->pass_percentage)
                ->selectRaw('user_id, MIN(submitted_at) as passed_at')
                ->groupBy('user_id')
                ->pluck('passed_at', 'user_id');
        }

        return $people->map(function (User $user) use ($course, $totalLessons, $enrollments, $assignments, $lessonStats, $finalPasses) {
            $enrollment = $enrollments->get($user->id);
            $stats = $lessonStats->get($user->id);
            $completedLessons = (int) ($stats->completed_lessons ?? 0);
            $progress = $totalLessons > 0 ? (int) round($completedLessons / $totalLessons * 100) : 0;
            $lastLessonAt = isset($stats->last_completed_at) ? Carbon::parse($stats->last_completed_at) : null;
            $passedAt = $finalPasses->has($user->id) ? Carbon::parse($finalPasses->get($user->id)) : null;
            $needsFinal = $course->assessment_mode === AssessmentMode::CourseFinal;

            $status = match (true) {
                ! $enrollment => self::NOT_ENROLLED,
                $progress === 100 && (! $needsFinal || $passedAt) => self::COMPLETED,
                $progress === 100 => self::FINAL_PENDING,
                $progress > 0 => self::IN_PROGRESS,
                default => self::NOT_STARTED,
            };

            $completedAt = $status === self::COMPLETED
                ? collect([$lastLessonAt, $passedAt])->filter()->max()
                : null;
            $assignment = $assignments->get($user->id);

            return [
                'user' => $user,
                'status' => $status,
                'progress' => $progress,
                'enrolled_at' => $enrollment?->enrolled_at,
                'completed_at' => $completedAt,
                'last_activity_at' => collect([$lastLessonAt, $passedAt])->filter()->max(),
                'assignment' => $assignment,
                'overdue' => $assignment?->isOverdue() && $status !== self::COMPLETED,
            ];
        });
    }
}
