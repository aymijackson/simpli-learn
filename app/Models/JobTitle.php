<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Lms\Models\Course;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A job title in a workspace ("Accountant", "Nurse"). People have one;
 * courses and library resources are recommended for any number. Titles are
 * matched by a normalised key, so typing an existing title in a different
 * case or spacing reuses it instead of creating a duplicate.
 */
class JobTitle extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'key'];

    public static function keyFor(string $name): string
    {
        return Str::lower(self::tidy($name));
    }

    /** Trimmed, single-spaced. */
    public static function tidy(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    /** The existing title with this name (any case/spacing), if there is one. */
    public static function findByName(string $name, int $tenantId): ?self
    {
        return static::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('key', self::keyFor($name))->first();
    }

    /** The existing title, or a new one — for owners, who may add titles. */
    public static function findOrCreateByName(string $name, int $tenantId): ?self
    {
        $name = self::tidy($name);
        if ($name === '') {
            return null;
        }

        return self::findByName($name, $tenantId)
            ?? static::create(['tenant_id' => $tenantId, 'name' => Str::limit($name, 120, ''), 'key' => self::keyFor($name)]);
    }

    /**
     * Titles from a list of names (checkboxes plus a comma-separated box),
     * creating new ones.
     *
     * @param  iterable<string>  $names
     * @return Collection<int, self>
     */
    public static function findOrCreateMany(iterable $names, int $tenantId): Collection
    {
        return collect($names)
            ->flatMap(fn ($name) => explode(',', (string) $name))
            ->map(fn ($name) => self::findOrCreateByName($name, $tenantId))
            ->filter()
            ->unique('id')
            ->values();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_job_title');
    }

    public function libraryResources(): BelongsToMany
    {
        return $this->belongsToMany(LibraryResource::class, 'library_resource_job_title', 'job_title_id', 'resource_id');
    }
}
