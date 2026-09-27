<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseDiscussion extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'course_id', 'lesson_id', 'user_id', 'title', 'body', 'last_activity_at'];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'staff_replied_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CourseDiscussionReply::class)->oldest();
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(CourseDiscussionReply::class, 'answer_reply_id');
    }

    public function isResolved(): bool
    {
        return $this->answer_reply_id !== null;
    }
}
