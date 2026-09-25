<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CourseCertificate extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'course_id', 'user_id', 'verification_token', 'issued_at', 'pdf_path'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The single place a course certificate gets created — generates a
     * collision-checked public verification token, same shape as
     * Elibrary\Cbt\Models\Certificate::issueFor().
     */
    public static function issueFor(Course $course, User $user): self
    {
        do {
            $token = Str::random(48);
        } while (static::withoutGlobalScope(TenantScope::class)->where('verification_token', $token)->exists());

        return static::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'verification_token' => $token,
            'issued_at' => now(),
        ]);
    }
}
