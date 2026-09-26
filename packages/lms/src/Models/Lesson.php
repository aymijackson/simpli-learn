<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'course_id', 'course_module_id', 'exam_id', 'title', 'content', 'position', 'is_preview'];

    protected function casts(): array
    {
        return [
            'is_preview' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class)->orderBy('position');
    }

    public function isAccessibleTo(User $user): bool
    {
        return $this->course->isEnrolled($user) || $this->is_preview;
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->progress()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->exists();
    }

    public function isUnlockedFor(User $user): bool
    {
        $course = $this->course;

        // Free preview lessons stay open to people browsing before they enrol.
        $previewing = $this->is_preview && ! $course->isEnrolled($user);

        if (! $previewing) {
            if (! $course->prerequisitesMetBy($user)) {
                return false;
            }

            if ($course->sequential_lessons && ($previous = $this->previousLesson($course)) && ! $previous->isCompletedBy($user)) {
                return false;
            }
        }

        return match ($course->assessment_mode) {
            AssessmentMode::PerLesson => $this->isUnlockedUnderPerLesson($user, $course),
            AssessmentMode::PerModule => $this->isUnlockedUnderPerModule($user, $course),
            default => true,
        };
    }

    /**
     * Returns the exam that must be passed before this lesson unlocks, if
     * it's currently locked, so the UI can point learners at it. Null when
     * unlocked or when the gate has no exam attached.
     */
    public function unlockRequirement(User $user): ?Exam
    {
        if ($this->isUnlockedFor($user)) {
            return null;
        }

        $course = $this->course;

        if ($course->assessment_mode === AssessmentMode::PerLesson) {
            $previous = $this->previousLesson($course);

            return $previous?->exam;
        }

        if ($course->assessment_mode === AssessmentMode::PerModule) {
            $previousModule = $this->previousModule($course);

            return $previousModule?->exam;
        }

        return null;
    }

    private function isUnlockedUnderPerLesson(User $user, Course $course): bool
    {
        $previous = $this->previousLesson($course);

        if (! $previous) {
            return true;
        }

        return $previous->isCompletedBy($user) && (! $previous->exam_id || $previous->exam->passedBy($user));
    }

    private function isUnlockedUnderPerModule(User $user, Course $course): bool
    {
        if (! $this->course_module_id) {
            return true;
        }

        $previousModule = $this->previousModule($course);

        if (! $previousModule) {
            return true;
        }

        $allCompleted = $previousModule->lessons->every(fn (Lesson $lesson) => $lesson->isCompletedBy($user));

        return $allCompleted && (! $previousModule->exam_id || $previousModule->exam->passedBy($user));
    }

    private function previousLesson(Course $course): ?self
    {
        $lessons = $course->lessons;
        $index = $lessons->search(fn (self $lesson) => $lesson->id === $this->id);

        return $index > 0 ? $lessons[$index - 1] : null;
    }

    private function previousModule(Course $course): ?CourseModule
    {
        $modules = $course->modules;
        $index = $modules->search(fn (CourseModule $module) => $module->id === $this->course_module_id);

        return $index !== false && $index > 0 ? $modules[$index - 1] : null;
    }
}
