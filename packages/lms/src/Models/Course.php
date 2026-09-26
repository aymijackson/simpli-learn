<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Enums\CourseCertificatePolicy;
use Elibrary\Lms\Enums\CourseLevel;
use Elibrary\Lms\Enums\CoursePricingPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'description', 'is_published', 'assessment_mode', 'final_exam_id',
        'pricing_policy', 'price', 'currency', 'certificate_policy', 'certificate_price', 'certificate_currency',
        'subtitle', 'cover_image_path', 'category', 'level', 'duration_minutes', 'instructor_name', 'instructor_bio', 'outcomes',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'assessment_mode' => AssessmentMode::class,
            'pricing_policy' => CoursePricingPolicy::class,
            'certificate_policy' => CourseCertificatePolicy::class,
            'level' => CourseLevel::class,
            'duration_minutes' => 'integer',
            'outcomes' => 'array',
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class)->latest();
    }

    public function coverUrl(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    /** "45 min", "3 hours", "2.5 hours" */
    public function durationLabel(): ?string
    {
        if (! $this->duration_minutes) {
            return null;
        }

        if ($this->duration_minutes < 60) {
            return $this->duration_minutes.' min';
        }

        $hours = round($this->duration_minutes / 60, 1);

        return ($hours == (int) $hours ? (int) $hours : $hours).' '.($hours == 1 ? 'hour' : 'hours');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(CourseCertificate::class);
    }

    public function certificateFor(User $user): ?CourseCertificate
    {
        return $this->certificates()->where('user_id', $user->id)->first();
    }

    public function finalExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'final_exam_id');
    }

    /**
     * The exams learners meet on the way through the course, in order, for
     * the current assessment mode: a quiz after a lesson, a test at the end
     * of a module, or the final exam. "after" is the last lesson before it.
     *
     * @return Collection<int, array{kind: string, label: string, exam: Exam, lesson: ?Lesson, module: ?CourseModule, after: ?Lesson}>
     */
    public function checkpoints(): Collection
    {
        $points = match ($this->assessment_mode) {
            AssessmentMode::PerLesson => $this->lessons
                ->filter(fn (Lesson $lesson) => $lesson->exam_id && $lesson->exam)
                ->map(fn (Lesson $lesson) => ['kind' => 'lesson', 'label' => 'Lesson quiz', 'exam' => $lesson->exam, 'lesson' => $lesson, 'module' => null, 'after' => $lesson]),
            AssessmentMode::PerModule => $this->modules
                ->filter(fn (CourseModule $module) => $module->exam_id && $module->exam && $module->lessons->isNotEmpty())
                ->map(fn (CourseModule $module) => ['kind' => 'module', 'label' => 'Module test', 'exam' => $module->exam, 'lesson' => null, 'module' => $module, 'after' => $module->lessons->last()]),
            AssessmentMode::CourseFinal => $this->final_exam_id && $this->finalExam
                ? collect([['kind' => 'final', 'label' => 'Final exam', 'exam' => $this->finalExam, 'lesson' => null, 'module' => null, 'after' => $this->lessons->last()]])
                : collect(),
            default => collect(),
        };

        return $points->values();
    }

    /** Whether the learner has got far enough through the course to sit this checkpoint. */
    public function checkpointIsReachable(array $checkpoint, User $user): bool
    {
        if (! $this->isEnrolled($user)) {
            return false;
        }

        return match ($checkpoint['kind']) {
            'lesson' => $checkpoint['lesson']->isUnlockedFor($user),
            'module' => $checkpoint['module']->lessons->first()?->isUnlockedFor($user) ?? true,
            default => true,
        };
    }

    /** passed, marking (submitted, essays not marked yet), open or locked. */
    public function checkpointStatus(array $checkpoint, User $user): string
    {
        $exam = $checkpoint['exam'];

        return match (true) {
            $exam->passedBy($user) => 'passed',
            $exam->attempts()->where('user_id', $user->id)->whereNotNull('submitted_at')->where('needs_marking', true)->exists() => 'marking',
            $this->checkpointIsReachable($checkpoint, $user) => 'open',
            default => 'locked',
        };
    }

    /** The lesson that follows a checkpoint, if any. */
    public function lessonAfterCheckpoint(array $checkpoint): ?Lesson
    {
        if (! $checkpoint['after']) {
            return null;
        }

        $index = $this->lessons->search(fn (Lesson $lesson) => $lesson->id === $checkpoint['after']->id);

        return $index === false ? null : $this->lessons->get($index + 1);
    }

    public function isEnrolled(User $user): bool
    {
        return $this->enrollments()->where('user_id', $user->id)->exists();
    }

    public function progressPercentFor(User $user): int
    {
        $total = $this->lessons()->count();

        if ($total === 0) {
            return 0;
        }

        $completed = LessonProgress::query()
            ->whereIn('lesson_id', $this->lessons()->pluck('id'))
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();

        return (int) round($completed / $total * 100);
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->progressPercentFor($user) === 100;
    }

    /**
     * Whether the course counts as "passed" for this user. For modes that
     * gate progress lesson-by-lesson or module-by-module, reaching 100%
     * completion already implies every required exam along the way was
     * passed, so completion is sufficient. Only course_final adds an extra
     * check on top of completion.
     */
    public function isPassedBy(User $user): bool
    {
        if (! $this->isCompletedBy($user)) {
            return false;
        }

        if ($this->assessment_mode === AssessmentMode::CourseFinal) {
            return $this->final_exam_id !== null && $this->finalExam->passedBy($user);
        }

        return true;
    }
}
