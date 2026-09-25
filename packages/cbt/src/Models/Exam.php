<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Elibrary\Cbt\Enums\CertificatePolicy;
use Elibrary\Cbt\Enums\NavigationMode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'description', 'instructions',
        'duration_minutes', 'enforce_time_limit', 'integrity_monitoring_enabled', 'pass_percentage', 'is_published',
        'navigation_mode', 'allow_backward_navigation',
        'allow_retakes', 'max_attempts', 'available_from', 'available_until',
        'randomize_questions', 'questions_per_attempt',
        'certificate_policy', 'certificate_price', 'certificate_currency',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'enforce_time_limit' => 'boolean',
            'integrity_monitoring_enabled' => 'boolean',
            'navigation_mode' => NavigationMode::class,
            'allow_backward_navigation' => 'boolean',
            'allow_retakes' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'randomize_questions' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class)->orderBy('position');
    }

    /**
     * The single source of truth for "question N" as used by one-at-a-time
     * navigation URLs — a plain 1-indexed position within this ordering, so
     * gaps in the raw `position` column never leak into routes.
     *
     * Without an attempt, this is always the plain owner-defined order (used
     * by manage/preview contexts). With one, and only then, it also applies
     * `questions_per_attempt` (a random subset, seeded off the attempt id so
     * it's picked once and never changes again for that attempt) and
     * `randomize_questions` (likewise a deterministic per-attempt shuffle,
     * not a fresh one on every request) — both derived on the fly from a
     * hash of the attempt and question ids rather than stored anywhere, so
     * there's nothing to keep in sync and no extra table.
     */
    public function orderedQuestions(?ExamAttempt $attempt = null): Collection
    {
        $questions = $this->questions()->with('options')->get();

        if ($attempt && $this->questions_per_attempt && $this->questions_per_attempt < $questions->count()) {
            $questions = $questions
                ->sortBy(fn (Question $question) => md5($attempt->id.'-select-'.$question->id))
                ->take($this->questions_per_attempt)
                ->values();
        }

        if ($attempt && $this->randomize_questions) {
            return $questions->sortBy(fn (Question $question) => md5($attempt->id.'-order-'.$question->id))->values();
        }

        return $questions->sortBy('position')->values();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * The tenant's one certificate-settings row, created on first use. Explicit
     * tenant_id filter (not the ambient resolved-tenant scope) so this always
     * resolves this exam's own tenant regardless of the calling context.
     */
    public function certificateSettings(): TenantCertificateSettings
    {
        // firstOrCreate() only populates the in-memory model with attributes
        // explicitly passed here — it does not re-fetch DB column defaults
        // after insert — so these must be spelled out even though the
        // migration already defaults them at the schema level.
        return TenantCertificateSettings::withoutGlobalScope(TenantScope::class)
            ->firstOrCreate(
                ['tenant_id' => $this->tenant_id],
                ['default_policy' => 'free', 'default_currency' => 'USD']
            );
    }

    /**
     * "inherit" resolves to the tenant's own default policy — the tenant's
     * default is never itself "inherit" (nothing above it to inherit from).
     */
    public function certificatePolicy(): CertificatePolicy
    {
        if ($this->certificate_policy && $this->certificate_policy !== CertificatePolicy::Inherit->value) {
            return CertificatePolicy::from($this->certificate_policy);
        }

        return CertificatePolicy::from($this->certificateSettings()->default_policy);
    }

    public function certificatePrice(): ?float
    {
        $price = $this->certificate_price ?? $this->certificateSettings()->default_price;

        return $price === null ? null : (float) $price;
    }

    public function certificateCurrency(): ?string
    {
        return $this->certificate_currency ?? $this->certificateSettings()->default_currency;
    }

    public function attemptsFor(User $user): HasMany
    {
        return $this->attempts()->where('user_id', $user->id)->latest('started_at');
    }

    public function passedBy(User $user): bool
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->where('score', '>=', $this->pass_percentage)
            ->exists();
    }

    /**
     * Why a brand-new attempt can't be started right now, or null if it can.
     * Deliberately silent about an existing in-progress attempt — that's a
     * "resume", not a "start", and is handled separately by the caller
     * before this is ever consulted.
     */
    public function startBlockReason(User $user): ?string
    {
        if ($this->available_from && now()->lt($this->available_from)) {
            return 'This exam opens '.$this->available_from->format('M j, Y g:ia').'.';
        }

        if ($this->available_until && now()->gt($this->available_until)) {
            return 'This exam closed '.$this->available_until->format('M j, Y g:ia').'.';
        }

        $submittedCount = $this->attempts()->where('user_id', $user->id)->whereNotNull('submitted_at')->count();

        if ($submittedCount === 0) {
            return null;
        }

        if (! $this->allow_retakes) {
            return 'You have already taken this exam and retakes are not allowed.';
        }

        if ($this->max_attempts && $submittedCount >= $this->max_attempts) {
            return "You've used all {$this->max_attempts} attempt(s) for this exam.";
        }

        return null;
    }
}
