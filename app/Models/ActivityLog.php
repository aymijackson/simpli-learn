<?php

namespace App\Models;

use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only record of security-relevant actions (logins, approvals, team
 * and payment changes, data requests) — the NDPA "accountability" trail.
 *
 * Not tenant-scoped automatically: central admins read across workspaces,
 * owners' views filter by tenant_id explicitly.
 */
class ActivityLog extends Model
{
    use MassPrunable;

    /** How long entries are kept before `php artisan model:prune` removes them. */
    public const RETENTION_DAYS = 730;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    /**
     * Record an action by the current user (or $actor) in the current
     * workspace (or $subject's, or $tenantId's).
     */
    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
        ?int $tenantId = null,
    ): self {
        $actor ??= auth()->user();
        $request = request();

        return static::create([
            'tenant_id' => $tenantId
                ?? app(Tenancy::class)->current()?->id
                ?? ($subject instanceof Tenant ? $subject->id : $subject?->getAttribute('tenant_id'))
                ?? $actor?->tenant_id,
            'user_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'action' => $action,
            'description' => mb_substr($description, 0, 500),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }
}
