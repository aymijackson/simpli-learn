<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Enums\CourseCertificatePolicy;
use Elibrary\Lms\Enums\CoursePricingPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'description', 'is_published', 'assessment_mode', 'final_exam_id',
        'pricing_policy', 'price', 'currency', 'certificate_policy', 'certificate_price', 'certificate_currency',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'assessment_mode' => AssessmentMode::class,
            'pricing_policy' => CoursePricingPolicy::class,
            'certificate_policy' => CourseCertificatePolicy::class,
        ];
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
