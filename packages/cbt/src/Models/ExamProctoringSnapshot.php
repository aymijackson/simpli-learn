<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Cbt\Enums\IntegrityEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A webcam photo from a proctored attempt: the ID check before it starts
 * ("id_check"), a regular photo ("interval"), or one taken the moment
 * something was flagged ("event"). Files live on the private disk and are
 * deleted, with their rows, once the exam's retention period has passed
 * since the attempt was submitted.
 */
class ExamProctoringSnapshot extends Model
{
    use BelongsToTenant, Prunable;

    public const KINDS = ['id_check', 'interval', 'event'];

    public const DISK = 'local';

    protected $fillable = ['tenant_id', 'exam_attempt_id', 'kind', 'event_type', 'path'];

    protected function casts(): array
    {
        return ['event_type' => IntegrityEventType::class];
    }

    /** Save an image for an attempt. $extension is jpg, png or webp. */
    public static function record(ExamAttempt $attempt, string $contents, string $extension, string $kind, ?string $eventType = null): self
    {
        $path = 'proctoring/'.$attempt->tenant_id.'/'.$attempt->id.'/'.Str::uuid().'.'.$extension;
        Storage::disk(self::DISK)->put($path, $contents);

        return static::withoutGlobalScopes()->create([
            'tenant_id' => $attempt->tenant_id,
            'exam_attempt_id' => $attempt->id,
            'kind' => $kind,
            'event_type' => $eventType,
            'path' => $path,
        ]);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function label(): string
    {
        return match ($this->kind) {
            'id_check' => 'ID check',
            'event' => $this->event_type?->label() ?? 'Flagged moment',
            default => 'Regular photo',
        };
    }

    /** Snapshots whose attempt was submitted longer ago than its exam keeps them. */
    public function prunable(): Builder
    {
        // Exams grouped by retention period, so the cutoff is a plain date
        // comparison (no database-specific date arithmetic).
        $examIdsByDays = Exam::withoutGlobalScopes()
            ->whereIn('id', ExamAttempt::withoutGlobalScopes()->select('exam_id')->whereIn('id', static::withoutGlobalScopes()->select('exam_attempt_id')))
            ->get(['id', 'proctoring_retention_days'])
            ->groupBy('proctoring_retention_days')
            ->map->pluck('id');

        if ($examIdsByDays->isEmpty()) {
            return static::withoutGlobalScopes()->whereRaw('1 = 0');
        }

        return static::withoutGlobalScopes()->whereIn('exam_attempt_id', function ($query) use ($examIdsByDays) {
            $query->select('id')->from('exam_attempts')->whereNotNull('submitted_at')->where(function ($query) use ($examIdsByDays) {
                foreach ($examIdsByDays as $days => $examIds) {
                    $query->orWhere(fn ($query) => $query->whereIn('exam_id', $examIds)->where('submitted_at', '<', now()->subDays(max(1, (int) $days))));
                }
            });
        });
    }

    protected function pruning(): void
    {
        Storage::disk(self::DISK)->delete($this->path);
    }
}
