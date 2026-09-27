<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'lesson_id', 'user_id', 'completed_at', 'video_percent', 'scorm_state', 'scorm_score'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'scorm_state' => 'array', 'scorm_score' => 'float'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
