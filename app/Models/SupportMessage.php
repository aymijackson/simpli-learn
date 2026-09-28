<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = ['conversation_id', 'tenant_id', 'user_id', 'from_staff', 'body'];

    protected function casts(): array
    {
        return ['from_staff' => 'boolean'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id')->withoutGlobalScopes();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }

    /** The shape the chat widget renders (text only — the browser sets it as textContent). */
    public function toChat(User $viewer): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'mine' => $this->user_id === $viewer->id,
            'from_staff' => $this->from_staff,
            'sender' => $this->from_staff
                ? ($this->conversation?->desk === SupportConversation::PLATFORM ? __('Platform support') : ($this->sender?->name ?? __('Support')))
                : ($this->sender?->name ?? __('Former member')),
            'at' => $this->created_at->toIso8601String(),
            'time' => $this->created_at->translatedFormat('j M, H:i'),
        ];
    }
}
