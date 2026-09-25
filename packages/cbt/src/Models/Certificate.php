<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Elibrary\Cbt\Enums\CertificateTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'exam_attempt_id', 'user_id', 'exam_id', 'verification_token', 'tier', 'issued_at', 'pdf_path'];

    protected function casts(): array
    {
        return [
            'tier' => CertificateTier::class,
            'issued_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * The single place a certificate gets created — generates a collision-checked
     * public verification token so callers never have to think about uniqueness.
     */
    public static function issueFor(ExamAttempt $attempt, CertificateTier $tier): self
    {
        do {
            $token = Str::random(48);
        } while (static::withoutGlobalScope(TenantScope::class)->where('verification_token', $token)->exists());

        return static::create([
            'exam_attempt_id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'exam_id' => $attempt->exam_id,
            'verification_token' => $token,
            'tier' => $tier->value,
            'issued_at' => now(),
        ]);
    }
}
