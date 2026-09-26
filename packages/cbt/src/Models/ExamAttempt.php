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

    protected $fillable = ['tenant_id', 'exam_id', 'user_id', 'started_at', 'submitted_at', 'score', 'needs_marking'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'needs_marking' => 'boolean',
        ];
    }

    /** Submitted, but essay answers still need marking — no score or pass/fail yet. */
    public function isAwaitingMarking(): bool
    {
        return $this->isSubmitted() && $this->needs_marking;
    }

    /**
     * Work out the score from the saved answers — the one place grading
     * happens, at submission and again after an essay is marked. While any
     * essay is unmarked the score stays null (so every "passed" check treats
     * the attempt as not passed) and needs_marking is set.
     */
    public function grade(): void
    {
        $questions = $this->exam->orderedQuestions($this);
        $answers = $this->answers()->with('selectedOptions')->get()->keyBy('question_id');
        $earned = 0.0;
        $possible = 0;
        $unmarked = false;

        foreach ($questions as $question) {
            $fraction = $question->scoreAnswer($answers->get($question->id));
            if ($fraction === null) {
                $unmarked = true;
            }
            $earned += ($fraction ?? 0.0) * $question->points;
            $possible += $question->points;
        }

        $this->forceFill([
            'score' => $unmarked ? null : ($possible === 0 ? 0 : (int) round($earned / $possible * 100)),
            'needs_marking' => $unmarked,
        ])->save();
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
