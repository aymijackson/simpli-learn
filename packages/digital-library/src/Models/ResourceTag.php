<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResourceTag extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'slug'];

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(LibraryResource::class, 'library_resource_tag', 'tag_id', 'resource_id');
    }
}
