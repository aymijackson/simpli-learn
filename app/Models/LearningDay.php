<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LearningDay extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'day'];

    protected function casts(): array
    {
        return ['day' => 'date'];
    }
}
