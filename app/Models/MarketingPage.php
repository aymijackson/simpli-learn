<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'content',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
