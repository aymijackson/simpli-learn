<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use BelongsToTenant;

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
