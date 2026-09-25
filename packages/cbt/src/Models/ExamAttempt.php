<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class ExamAttempt extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'exam_id', 'user_id', 'started_at', 'submitted_at', 'score'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class, 'attempt_id');
    }

    public function integrityEvents(): HasMany
    {
        return $this->hasMany(ExamIntegrityEvent::class, 'exam_attempt_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'exam_attempt_id');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function passed(): bool
    {
        return $this->isSubmitted() && $this->score >= $this->exam->pass_percentage;
    }

    public function deadline(): Carbon
    {
        return $this->started_at->copy()->addMinutes($this->exam->duration_minutes);
    }

    public function hasExpired(): bool
    {
        return $this->exam->enforce_time_limit
            && ! $this->isSubmitted()
            && now()->greaterThan($this->deadline());
    }

    /**
     * How many questions have a saved answer row for this attempt. Used as
     * the sole source of truth for one-at-a-time navigation progress —
     * deliberately not a stored "current position" column, so there's
     * nothing for a tampered request to desync: the only way to increase
     * this is to actually save a valid answer for the current page.
     */
    public function answeredPageCount(): int
    {
        return $this->answers()->count();
    }

    public function nextPageNumber(): int
    {
        return $this->answeredPageCount() + 1;
    }

    /**
     * Wall-clock minutes from start to submit — null while still in
     * progress. Single source of truth for "time taken," shared by the
     * analytics aggregates and the per-attempt display so they can't drift.
     */
    public function durationMinutes(): ?float
    {
        if (! $this->isSubmitted()) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->submitted_at) / 60;
    }
}
