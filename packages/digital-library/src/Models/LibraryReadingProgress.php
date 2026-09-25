<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryReadingProgress extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'resource_file_id', 'user_id', 'position'];

    public function file(): BelongsTo
    {
        return $this->belongsTo(LibraryResourceFile::class, 'resource_file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
