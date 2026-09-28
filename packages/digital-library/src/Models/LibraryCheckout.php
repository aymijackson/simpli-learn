<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryCheckout extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'resource_id', 'user_id', 'checked_out_at', 'due_at', 'reminder_sent_at', 'returned_at'];

    protected function casts(): array
    {
        return [
            'checked_out_at' => 'datetime',
            'due_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
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
     * Past its due date. Access ends at that moment (see
     * LibraryResource::hasActiveCheckoutFor); the hourly
     * `library:process-loans` job then returns it, freeing the copy.
     */
    public function isOverdue(): bool
    {
        return $this->isActive() && now()->greaterThan($this->due_at);
    }
}
