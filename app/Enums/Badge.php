<?php

namespace App\Enums;

/** Achievements learners earn automatically, in display order. */
enum Badge: string
{
    case FirstLesson = 'first_lesson';
    case FirstCourse = 'first_course';
    case FiveCourses = 'five_courses';
    case FirstPass = 'first_pass';
    case PerfectScore = 'perfect_score';
    case Streak7 = 'streak_7';
    case Streak30 = 'streak_30';

    public function label(): string
    {
        return match ($this) {
            self::FirstLesson => __('First step'),
            self::FirstCourse => __('Course finisher'),
            self::FiveCourses => __('Dedicated learner'),
            self::FirstPass => __('Exam passed'),
            self::PerfectScore => __('Perfect score'),
            self::Streak7 => __('Week streak'),
            self::Streak30 => __('Month streak'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FirstLesson => __('Completed your first lesson.'),
            self::FirstCourse => __('Completed a whole course.'),
            self::FiveCourses => __('Completed five courses.'),
            self::FirstPass => __('Passed an exam.'),
            self::PerfectScore => __('Scored 100% on an exam.'),
            self::Streak7 => __('Learned on 7 days in a row.'),
            self::Streak30 => __('Learned on 30 days in a row.'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FirstLesson => 'play',
            self::FirstCourse, self::FiveCourses => 'academic-cap',
            self::FirstPass => 'clipboard-check',
            self::PerfectScore => 'star',
            self::Streak7, self::Streak30 => 'fire',
        };
    }

    /** Tailwind classes for the badge medallion. */
    public function tone(): string
    {
        return match ($this) {
            self::FirstLesson => 'bg-sky-100 text-sky-700',
            self::FirstCourse => 'bg-indigo-100 text-indigo-700',
            self::FiveCourses => 'bg-violet-100 text-violet-700',
            self::FirstPass => 'bg-emerald-100 text-emerald-700',
            self::PerfectScore => 'bg-amber-100 text-amber-700',
            self::Streak7 => 'bg-orange-100 text-orange-700',
            self::Streak30 => 'bg-rose-100 text-rose-700',
        };
    }
}
