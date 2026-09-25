<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryHold extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'resource_id', 'user_id', 'requested_at', 'notified_at', 'expires_at', 'fulfilled_at'];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'notified_at' => 'datetime',
            'expires_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(LibraryResource::class, 'resource_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A hold is "live" once notified — the user has a claim window
     * (expires_at) to actually check out before their turn is forfeited.
     * Computed live from timestamps, nothing is ever advanced by a job.
     */
    public function hasLiveOffer(): bool
    {
        return $this->notified_at !== null
            && $this->fulfilled_at === null
            && $this->expires_at !== null
            && now()->lessThan($this->expires_at);
    }

    public function hasForfeitedOffer(): bool
    {
        return $this->notified_at !== null
            && $this->fulfilled_at === null
            && $this->expires_at !== null
            && now()->greaterThanOrEqualTo($this->expires_at);
    }

    /**
     * 1-based queue position among still-waiting (not fulfilled, not
     * forfeited) holds for the same resource, ordered by request time —
     * derived live, never a stored column, so there's nothing to desync.
     */
    public function position(): int
    {
        return static::query()
            ->where('resource_id', $this->resource_id)
            ->whereNull('fulfilled_at')
            ->where('requested_at', '<', $this->requested_at)
            ->get()
            ->reject(fn (self $hold) => $hold->hasForfeitedOffer())
            ->count() + 1;
    }
}
