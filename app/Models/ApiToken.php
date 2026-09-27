<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use BelongsToTenant;

    public const PREFIX = 'elib_';

    protected $fillable = ['tenant_id', 'created_by_user_id', 'name', 'token_hash', 'prefix', 'can_write'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'can_write' => 'boolean',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Create a key and return it with its plain-text value, which is never
     * stored and can't be shown again.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(int $tenantId, User $by, string $name, bool $canWrite): array
    {
        $plain = self::PREFIX.Str::random(40);

        $token = self::create([
            'tenant_id' => $tenantId,
            'created_by_user_id' => $by->id,
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
            'prefix' => substr($plain, 0, 12),
            'can_write' => $canWrite,
        ]);

        return [$token, $plain];
    }

    /** The active key matching a presented value, from any workspace. */
    public static function findActive(?string $plain): ?self
    {
        if (! $plain || ! str_starts_with($plain, self::PREFIX)) {
            return null;
        }

        return self::withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->first();
    }
}
