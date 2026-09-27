<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Contracts\SharedAcrossTenants;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Support\SharedCourses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model implements SharedAcrossTenants
{
    use BelongsToTenant;

    public function sharedWithTenant(Builder $query, int $tenantId): void
    {
        $query->whereIn($this->qualifyColumn('course_id'), SharedCourses::ids($tenantId));
    }


    protected $fillable = ['tenant_id', 'course_id', 'title', 'position', 'exam_id'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
