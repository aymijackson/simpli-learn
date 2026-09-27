<?php

namespace Elibrary\Lms\Support;

use App\Models\User;
use App\Support\Achievements;
use Elibrary\Lms\Certificates\CourseCertificateService;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;

/**
 * Marks a lesson complete and runs what follows from it (certificate, streak,
 * badges) — shared by the "Mark as complete" button, watched videos and
 * finished SCORM packages.
 */
class LessonCompletion
{
    public static function complete(Course $course, Lesson $lesson, User $user): void
    {
        $lesson->progress()->updateOrCreate(['user_id' => $user->id], ['completed_at' => now()]);

        if ($course->isEnrolled($user)) {
            app(CourseCertificateService::class)->issueIfPassedAndFree($course, $user);
            Achievements::recordActivity($user);
            Achievements::evaluate($user);
        }
    }
}
