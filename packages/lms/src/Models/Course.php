<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Contracts\SharedAcrossTenants;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Enums\CourseCertificatePolicy;
use Elibrary\Lms\Enums\CourseLevel;
use Elibrary\Lms\Enums\CoursePricingPolicy;
use Elibrary\Lms\Support\SharedCourses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Course extends Model implements SharedAcrossTenants
{
    use BelongsToTenant;

    /** Published courses other workspaces open to everyone or share with this one. */
    public function sharedWithTenant(Builder $query, int $tenantId): void
    {
        $query->whereIn($this->qualifyColumn('id'), SharedCourses::ids($tenantId));
    }

    /**
     * Resolve {course:slug} to this workspace's own course first, so a shared
     * course with the same slug never shadows it.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $tenantId = app(Tenancy::class)->id();

        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->when($tenantId, fn ($query) => $query->orderByRaw('CASE WHEN '.$this->qualifyColumn('tenant_id').' = ? THEN 0 ELSE 1 END', [$tenantId]));
    }

    /** True when this course belongs to a workspace other than the current one. */
    public function isShared(): bool
    {
        $tenantId = app(Tenancy::class)->id();

        return $tenantId !== null && $this->tenant_id !== $tenantId;
    }

    /** Paid enrolment applies only to the owning workspace; shared learners join free. */
    public function isPaidHere(): bool
    {
        return $this->pricing_policy === CoursePricingPolicy::Paid && ! $this->isShared();
    }

    /** Likewise a paid certificate: other workspaces' learners get it free. */
    public function certificateIsPaidHere(): bool
    {
        return $this->certificate_policy === CourseCertificatePolicy::Paid && ! $this->isShared();
    }

    /** Workspaces a platform admin has shared this course with. */
    public function sharedWorkspaces(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'course_shares')->withTimestamps();
    }


    protected $fillable = [
        'tenant_id', 'title', 'slug', 'description', 'is_published', 'is_public', 'assessment_mode', 'sequential_lessons', 'final_exam_id',
        'pricing_policy', 'price', 'currency', 'certificate_policy', 'certificate_price', 'certificate_currency',
        'subtitle', 'cover_image_path', 'category', 'level', 'duration_minutes', 'instructor_name', 'instructor_bio', 'outcomes',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_public' => 'boolean',
            'assessment_mode' => AssessmentMode::class,
            'sequential_lessons' => 'boolean',
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

    /** Courses that must be passed before this one opens. */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'course_prerequisites', 'course_id', 'prerequisite_course_id')
            ->withPivot(['tenant_id', 'learning_path_id'])
            ->withTimestamps()
            ->orderBy('title');
    }

    /** Members who teach this course. */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_instructors')->withPivot('tenant_id')->withTimestamps()->orderBy('name');
    }

    /** Learning paths that include this course. */
    public function paths(): BelongsToMany
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_courses')->withPivot('position');
    }

    /** Courses that list this one as a prerequisite. */
    public function unlocks(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'course_prerequisites', 'prerequisite_course_id', 'course_id')->orderBy('title');
    }

    /** @var array<int, Collection<int, Course>> Unmet prerequisites per user id, for this request. */
    private array $unmetPrerequisites = [];

    /** @return Collection<int, Course> Prerequisite courses this person hasn't passed yet. */
    public function unmetPrerequisitesFor(User $user): Collection
    {
        return $this->unmetPrerequisites[$user->id] ??= $this->prerequisites
            ->reject(fn (self $prerequisite) => $prerequisite->isPassedBy($user))
            ->values();
    }

    public function prerequisitesMetBy(User $user): bool
    {
        return $this->unmetPrerequisitesFor($user)->isEmpty();
    }

    /**
     * Whether making $candidate a prerequisite of this course would create a
     * loop (this course is already, directly or indirectly, required by it).
     */
    public function wouldCreatePrerequisiteLoop(self $candidate): bool
    {
        $seen = [];
        $queue = [$candidate->id];

        while ($queue) {
            $id = array_shift($queue);
            if ($id === $this->id) {
                return true;
            }
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            array_push($queue, ...DB::table('course_prerequisites')->where('course_id', $id)->pluck('prerequisite_course_id')->all());
        }

        return false;
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
        if (! $this->isEnrolled($user) || ! $this->prerequisitesMetBy($user)) {
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
