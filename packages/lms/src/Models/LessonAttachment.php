<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Lms\Enums\LessonAttachmentAccessLevel;
use Elibrary\Lms\Enums\LessonAttachmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAttachment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'lesson_id', 'title', 'type', 'access_level',
        'disk_path', 'mime_type', 'original_filename', 'size', 'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => LessonAttachmentType::class,
            'access_level' => LessonAttachmentAccessLevel::class,
            'size' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function disk(): string
    {
        return $this->access_level === LessonAttachmentAccessLevel::Open ? 'public' : 'local';
    }
}
