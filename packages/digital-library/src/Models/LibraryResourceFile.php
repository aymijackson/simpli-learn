<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Library\Enums\LibraryFileAccessLevel;
use Elibrary\Library\Enums\LibraryFileFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryResourceFile extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'resource_id', 'title', 'format', 'access_level',
        'disk_path', 'mime_type', 'original_filename', 'size', 'position',
    ];

    protected function casts(): array
    {
        return [
            'format' => LibraryFileFormat::class,
            'access_level' => LibraryFileAccessLevel::class,
            'size' => 'integer',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(LibraryResource::class, 'resource_id');
    }

    public function readingProgress(): HasMany
    {
        return $this->hasMany(LibraryReadingProgress::class, 'resource_file_id');
    }

    /**
     * All library files live on the private disk and are only ever served
     * through the app (see LibraryResourceFileController). Open files used
     * to sit on the public disk; a migration moved them.
     */
    public function disk(): string
    {
        return 'local';
    }
}
