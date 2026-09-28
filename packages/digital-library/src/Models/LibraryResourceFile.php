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
        'disk_path', 'pdf_path', 'mime_type', 'original_filename', 'size', 'position',
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

    public function extension(): string
    {
        return strtolower(pathinfo($this->disk_path, PATHINFO_EXTENSION));
    }

    /**
     * How it opens inside the platform: 'pdf', 'epub', 'image', 'video',
     * 'audio' or 'text' — or null when it can't be viewed (a zip, or an
     * Office file whose PDF copy couldn't be made).
     */
    public function viewer(): ?string
    {
        return match ($this->format) {
            LibraryFileFormat::Pdf => 'pdf',
            LibraryFileFormat::Document => $this->pdf_path ? 'pdf' : null,
            LibraryFileFormat::Epub => 'epub',
            LibraryFileFormat::Image => 'image',
            LibraryFileFormat::Video => 'video',
            LibraryFileFormat::Audio => 'audio',
            LibraryFileFormat::Text => 'text',
            default => null,
        };
    }

    /** The stored file shown inline: the PDF copy for Office documents. */
    public function viewPath(): string
    {
        return $this->pdf_path ?: $this->disk_path;
    }

    /** A Content-Type from our fixed list, never from what was uploaded. */
    public function viewMime(): string
    {
        return $this->pdf_path ? 'application/pdf' : LibraryFileFormat::safeMimeFor($this->extension());
    }
}
