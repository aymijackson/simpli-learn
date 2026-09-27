<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A named, ordered set of courses learners work through as one. */
class LearningPath extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'title', 'slug', 'description', 'is_published', 'is_sequential'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_sequential' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'learning_path_courses')
            ->withPivot(['tenant_id', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * Where this person is on each course of the path.
     *
     * @return Collection<int, array{course: Course, status: string, percent: int}>
     *                                                                           status: passed, in_progress, not_started or locked
     */
    public function stepsFor(User $user): Collection
    {
        return $this->courses->map(function (Course $course) use ($user) {
            $enrolled = $course->isEnrolled($user);
            $status = match (true) {
                $enrolled && $course->isPassedBy($user) => 'passed',
                ! $course->prerequisitesMetBy($user) => 'locked',
                $enrolled => 'in_progress',
                default => 'not_started',
            };

            return [
                'course' => $course,
                'status' => $status,
                'percent' => $enrolled ? $course->progressPercentFor($user) : 0,
            ];
        });
    }

    /** Percentage of the path's courses this person has passed. */
    public static function percentOf(Collection $steps): int
    {
        return $steps->isEmpty() ? 0 : (int) round($steps->where('status', 'passed')->count() / $steps->count() * 100);
    }

    /**
     * Replace the path's courses (ids in order) and, for a sequential path,
     * make each course require the one before it. Refuses an order that
     * would contradict prerequisites already set on the courses.
     *
     * @param  array<int, int>  $courseIds
     */
    public function syncCourses(array $courseIds): void
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));

        DB::transaction(function () use ($courseIds) {
            $this->courses()->sync(collect($courseIds)->mapWithKeys(fn ($id, $position) => [
                $id => ['tenant_id' => $this->tenant_id, 'position' => $position],
            ])->all());

            DB::table('course_prerequisites')->where('learning_path_id', $this->id)->delete();

            if (! $this->is_sequential) {
                return;
            }

            $courses = Course::whereIn('id', $courseIds)->get()->keyBy('id');
            foreach (array_slice($courseIds, 1) as $index => $id) {
                $course = $courses[$id];
                $previous = $courses[$courseIds[$index]];

                if ($course->wouldCreatePrerequisiteLoop($previous)) {
                    throw ValidationException::withMessages([
                        'course_ids' => "\"{$course->title}\" is already required by \"{$previous->title}\", so it can't come after it in this path.",
                    ]);
                }

                DB::table('course_prerequisites')->insertOrIgnore([
                    'tenant_id' => $this->tenant_id,
                    'course_id' => $course->id,
                    'prerequisite_course_id' => $previous->id,
                    'learning_path_id' => $this->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
