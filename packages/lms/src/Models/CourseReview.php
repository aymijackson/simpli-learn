<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A learner's star rating (1–5) and optional comment on a course they're enrolled in. */
class CourseReview extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'course_id', 'user_id', 'stars', 'comment'];

    protected function casts(): array
    {
        return ['stars' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
