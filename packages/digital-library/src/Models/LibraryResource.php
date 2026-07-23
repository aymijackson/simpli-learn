<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LibraryResource extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'title', 'slug', 'author', 'category', 'description', 'external_url', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
