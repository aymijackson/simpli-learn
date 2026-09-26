<?php

namespace App\Models;

use App\Enums\Badge;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class UserBadge extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'badge', 'earned_at'];

    protected function casts(): array
    {
        return [
            'badge' => Badge::class,
            'earned_at' => 'datetime',
        ];
    }
}
