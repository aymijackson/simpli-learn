<?php

namespace Elibrary\Lms\Support;

use App\Enums\Module;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Contracts\ExamPlacements;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseModule;
use Elibrary\Lms\Models\Lesson;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

/**
 * Exams used as course checkpoints (lesson quiz, module test, final exam)
 * belong to their course: they're taken from the course, by people enrolled
 * in it who have reached that point, and lead back into it afterwards.
 * Only attachments that the course's assessment mode actually uses count.
 */
class CourseExamPlacements implements ExamPlacements
{
    public function __construct(private Tenancy $tenancy)
    {
    }

    public function embeddedExamIds(): array
    {
        if (! $this->lmsEnabled()) {
            return [];
        }

        return collect()
            ->merge(Course::where('assessment_mode', AssessmentMode::CourseFinal)->whereNotNull('final_exam_id')->pluck('final_exam_id'))
            ->merge(Lesson::whereNotNull('exam_id')->whereHas('course', fn ($query) => $query->where('assessment_mode', AssessmentMode::PerLesson))->pluck('exam_id'))
            ->merge(CourseModule::whereNotNull('exam_id')->whereHas('course', fn ($query) => $query->where('assessment_mode', AssessmentMode::PerModule))->pluck('exam_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function sharedExamIds(int $tenantId): ?Builder
    {
        return SharedCourses::examIds($tenantId);
    }

    public function markableExamIds(User $user): ?array
    {
        return $user->isOwner() ? null : Teaching::examIds($user);
    }

    public function markersFor(Exam $exam): Collection
    {
        return $this->lmsEnabled() ? Teaching::instructorsForExam($exam->id) : collect();
    }

    public function accessBlockReason(Exam $exam, User $user): ?string
    {
        $placements = $this->placements($exam);

        if ($placements->isEmpty() || $user->isOwner()) {
            return null;
        }

        $enrolled = $placements->filter(fn ($placement) => $placement['course']->isEnrolled($user));

        if ($enrolled->contains(fn ($placement) => $placement['course']->checkpointIsReachable($placement['checkpoint'], $user))) {
            return null;
        }

        if ($enrolled->isNotEmpty()) {
            ['course' => $course, 'checkpoint' => $checkpoint] = $enrolled->first();

            return match ($checkpoint['kind']) {
                'lesson' => __('This quiz opens when you reach “:lesson” in :course.', ['lesson' => $checkpoint['lesson']->title, 'course' => $course->title]),
                'module' => __('This test opens when you reach “:module” in :course.', ['module' => $checkpoint['module']->title, 'course' => $course->title]),
                default => __('Keep going in :course to unlock this exam.', ['course' => $course->title]),
            };
        }

        return __('This exam is part of the course “:course”. Enrol in the course to take it.', ['course' => $placements->first()['course']->title]);
    }

    public function contextFor(Exam $exam, User $user): ?array
    {
        $placements = $this->placements($exam);
        $placement = $placements->first(fn ($placement) => $placement['course']->isEnrolled($user)) ?? $placements->first();

        if (! $placement) {
            return null;
        }

        ['course' => $course, 'checkpoint' => $checkpoint] = $placement;
        $courseUrl = route('lms.courses.show', $course);
        [$nextLabel, $nextUrl] = [__('Back to the course'), $courseUrl];

        if ($course->isEnrolled($user) && $exam->passedBy($user)) {
            $lesson = $course->lessonAfterCheckpoint($checkpoint);
            if ($lesson && $lesson->isUnlockedFor($user)) {
                [$nextLabel, $nextUrl] = [__('Continue: :title', ['title' => $lesson->title]), route('lms.lessons.show', [$course, $lesson])];
            }
        }

        return [
            'title' => $course->title,
            'url' => $courseUrl,
            'checkpoint' => $checkpoint['label'],
            'next_label' => $nextLabel,
            'next_url' => $nextUrl,
        ];
    }

    /** @return Collection<int, array{course: Course, checkpoint: array}> */
    private function placements(Exam $exam): Collection
    {
        if (! $this->lmsEnabled()) {
            return collect();
        }

        return Course::query()
            ->where(fn ($query) => $query
                ->where('final_exam_id', $exam->id)
                ->orWhereHas('lessons', fn ($lessons) => $lessons->where('exam_id', $exam->id))
                ->orWhereHas('modules', fn ($modules) => $modules->where('exam_id', $exam->id)))
            ->orderBy('title')
            ->get()
            ->flatMap(fn (Course $course) => $course->checkpoints()
                ->filter(fn ($checkpoint) => $checkpoint['exam']->id === $exam->id)
                ->map(fn ($checkpoint) => ['course' => $course, 'checkpoint' => $checkpoint]))
            ->values();
    }

    private function lmsEnabled(): bool
    {
        return (bool) $this->tenancy->current()?->hasModule(Module::Lms);
    }
}
