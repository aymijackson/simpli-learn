<?php

namespace App\Support;

use App\Models\User;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * "Recommended for your role": published courses and library resources an
 * owner has marked as recommended for the person's job title. Courses they
 * are already enrolled in are left out.
 */
class Recommendations
{
    /** Scope a course or resource query to what's recommended for this person's title. */
    public static function forRole(Builder $query, User $user): Builder
    {
        return $query->whereHas('jobTitles', fn (Builder $titles) => $titles->whereKey($user->job_title_id ?? 0));
    }

    /** @return Collection<int, Course> */
    public static function courses(User $user, int $limit = 4): Collection
    {
        if (! $user->job_title_id) {
            return collect();
        }

        return self::forRole(Course::query(), $user)
            ->where('is_published', true)
            ->whereNotIn('id', Enrollment::where('user_id', $user->id)->select('course_id'))
            ->withCount(['lessons', 'reviews'])
            ->withAvg('reviews', 'stars')
            ->latest()
            ->take($limit)
            ->get();
    }

    /** @return Collection<int, LibraryResource> */
    public static function libraryResources(User $user, int $limit = 6): Collection
    {
        if (! $user->job_title_id) {
            return collect();
        }

        return self::forRole(LibraryResource::query(), $user)
            ->where('is_published', true)
            ->latest()
            ->take($limit)
            ->get();
    }
}
