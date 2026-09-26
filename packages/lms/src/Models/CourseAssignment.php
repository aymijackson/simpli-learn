<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A course an owner has told someone to complete, optionally by a deadline.
 * Assigning also enrolls the person, so completion is tracked the usual way.
 */
class CourseAssignment extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'course_id', 'user_id', 'assigned_by_user_id', 'due_at', 'reminded_at'];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }
}
