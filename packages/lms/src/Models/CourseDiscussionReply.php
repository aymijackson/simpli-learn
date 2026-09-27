<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseDiscussionReply extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'course_discussion_id', 'user_id', 'body', 'is_staff'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean'];
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(CourseDiscussion::class, 'course_discussion_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
