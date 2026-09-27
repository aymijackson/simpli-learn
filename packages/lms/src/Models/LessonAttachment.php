<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Contracts\SharedAcrossTenants;
use Elibrary\Lms\Enums\LessonAttachmentAccessLevel;
use Elibrary\Lms\Enums\LessonAttachmentType;
use Elibrary\Lms\Support\SharedCourses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAttachment extends Model implements SharedAcrossTenants
{
    use BelongsToTenant;

    public function sharedWithTenant(Builder $query, int $tenantId): void
    {
        $query->whereIn($this->qualifyColumn('lesson_id'), SharedCourses::lessonIds($tenantId));
    }


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
