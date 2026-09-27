<?php

namespace Elibrary\Lms\Support;

use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\Course;
use Illuminate\Support\Facades\DB;

/**
 * Who may work on which course. Owners manage everything; instructors manage
 * the content, report and marking of the courses they're assigned to, and
 * nothing else (pricing, publishing, sharing and exams stay with owners).
 */
class Teaching
{
    /** @return array<int, int> Ids of courses this person teaches in the current workspace. */
    public static function courseIds(User $user): array
    {
        return DB::table('course_instructors')
            ->where('tenant_id', app(Tenancy::class)->id())
            ->where('user_id', $user->id)
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function isInstructor(User $user): bool
    {
        return self::courseIds($user) !== [];
    }

    /** Owners and people who teach something get the (trimmed) back office. */
    public static function canTeach(?User $user): bool
    {
        return $user && ($user->isOwner() || self::isInstructor($user));
    }

    public static function canManage(User $user, Course $course): bool
    {
        return $user->isOwner() || in_array($course->id, self::courseIds($user), true);
    }

    public static function authorize(Course $course): void
    {
        abort_unless(self::canManage(auth()->user(), $course), 403, 'You can only manage the courses you teach.');
    }

    /**
     * Exams used anywhere in the courses this person teaches (lesson quizzes,
     * module tests, final exams) — the essays they may mark.
     *
     * @return array<int, int>
     */
    public static function examIds(User $user): array
    {
        $courseIds = self::courseIds($user);
        if ($courseIds === []) {
            return [];
        }

        return DB::table('courses')->whereIn('id', $courseIds)->whereNotNull('final_exam_id')->pluck('final_exam_id')
            ->merge(DB::table('lessons')->whereIn('course_id', $courseIds)->whereNotNull('exam_id')->pluck('exam_id'))
            ->merge(DB::table('course_modules')->whereIn('course_id', $courseIds)->whereNotNull('exam_id')->pluck('exam_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** Instructors of any course that uses this exam. */
    public static function instructorsForExam(int $examId): \Illuminate\Support\Collection
    {
        $courseIds = DB::table('courses')->where('final_exam_id', $examId)->pluck('id')
            ->merge(DB::table('lessons')->where('exam_id', $examId)->pluck('course_id'))
            ->merge(DB::table('course_modules')->where('exam_id', $examId)->pluck('course_id'))
            ->unique();

        return User::whereIn('id', DB::table('course_instructors')->whereIn('course_id', $courseIds)->pluck('user_id'))
            ->active()
            ->get();
    }
}
