<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Cbt\Enums\IntegrityEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamIntegrityEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'exam_attempt_id', 'event_type', 'metadata'];

    protected function casts(): array
    {
        return [
            'event_type' => IntegrityEventType::class,
            'metadata' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}
