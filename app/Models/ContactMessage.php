<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * A message from the public contact form. Name, email, subject and message
 * are encrypted in the database; the sender's IP is stored only as a keyed
 * hash (for rate limits and abuse checks). Deleted after a year.
 */
class ContactMessage extends Model
{
    use Prunable;

    protected $fillable = ['name', 'email', 'subject', 'message', 'ip_hash', 'user_agent', 'read_at'];

    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'email' => 'encrypted',
            'subject' => 'encrypted',
            'message' => 'encrypted',
            'read_at' => 'datetime',
        ];
    }

    public static function hashIp(?string $ip): string
    {
        return hash_hmac('sha256', (string) $ip, config('app.key'));
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subYear());
    }
}
