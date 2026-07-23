<?php

namespace Elibrary\Lms\Enums;

enum AssessmentMode: string
{
    case None = 'none';
    case PerLesson = 'per_lesson';
    case PerModule = 'per_module';
    case CourseFinal = 'course_final';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No exams required',
            self::PerLesson => 'Require an exam after each lesson',
            self::PerModule => 'Require an exam after each module',
            self::CourseFinal => 'Require a final exam for the whole course',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::None => 'Learners move through lessons freely.',
            self::PerLesson => 'A lesson can be assigned an exam; learners must pass it before the next lesson unlocks.',
            self::PerModule => 'A module can be assigned an exam; learners must pass it before the next module unlocks.',
            self::CourseFinal => 'Lessons are freely accessible, but the course only counts as passed once the final exam is passed.',
        };
    }
}
