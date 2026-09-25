<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryCheckout extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'resource_id', 'user_id', 'checked_out_at', 'due_at', 'returned_at'];

    protected function casts(): array
    {
        return [
            'checked_out_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
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

    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    /**
     * Overdue is just a display/nudge state — it does not free the copy.
     * Only an explicit return does that (no auto-expiry, this app has no
     * cron infrastructure and everything is computed live like this).
     */
    public function isOverdue(): bool
    {
        return $this->isActive() && now()->greaterThan($this->due_at);
    }
}
