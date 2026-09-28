<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A support chat between one person and a desk:
 *  - "workspace": a learner and their workspace's owners;
 *  - "platform": a workspace owner and the platform admins.
 * Each person has at most one conversation per desk; it is reopened when
 * they write again after it was marked resolved.
 */
class SupportConversation extends Model
{
    use BelongsToTenant;

    public const WORKSPACE = 'workspace';

    public const PLATFORM = 'platform';

    protected $fillable = ['tenant_id', 'desk', 'user_id', 'status', 'last_message_at', 'requester_read_id', 'staff_read_id', 'staff_emailed_at'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'staff_emailed_at' => 'datetime',
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

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'conversation_id')->withoutGlobalScopes()->orderBy('id');
    }

    /** Which desk a person writes to from their bubble. */
    public static function deskFor(User $user): ?string
    {
        if (! $user->tenant_id) {
            return null; // platform admins answer, they don't ask
        }

        return $user->isOwner() ? self::PLATFORM : self::WORKSPACE;
    }

    /** Does this person answer this conversation? */
    public function isStaff(User $user): bool
    {
        return $this->desk === self::PLATFORM
            ? $user->tenant_id === null
            : $user->tenant_id === $this->tenant_id && $user->isOwner() && $user->id !== $this->user_id;
    }

    public function unreadForRequester(): int
    {
        return $this->messages()->where('from_staff', true)->where('id', '>', $this->requester_read_id)->count();
    }

    /** Conversations with a message from the person that staff haven't read yet. */
    public function scopeAwaitingStaff(Builder $query): Builder
    {
        return $query->whereExists(fn ($messages) => $messages->from('support_messages')
            ->whereColumn('support_messages.conversation_id', 'support_conversations.id')
            ->where('support_messages.from_staff', false)
            ->whereColumn('support_messages.id', '>', 'support_conversations.staff_read_id'));
    }
}
