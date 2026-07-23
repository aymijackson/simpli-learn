<?php

namespace App\Enums;

enum Module: string
{
    case Lms = 'lms';
    case Cbt = 'cbt';
    case Library = 'library';

    public function label(): string
    {
        return match ($this) {
            self::Lms => 'Learning Management',
            self::Cbt => 'Computer-Based Testing',
            self::Library => 'Digital Library',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Lms => 'Learning',
            self::Cbt => 'CBT',
            self::Library => 'Library',
        };
    }

    public function tagline(): string
    {
        return match ($this) {
            self::Lms => 'Courses, lessons, and progress tracking.',
            self::Cbt => 'Timed exams with instant, auto-graded results.',
            self::Library => 'A searchable catalog of study resources.',
        };
    }

    /** The named route for this module's landing page. */
    public function routeName(): string
    {
        return match ($this) {
            self::Lms => 'lms.courses.index',
            self::Cbt => 'cbt.exams.index',
            self::Library => 'library.resources.index',
        };
    }

    /** Prefix shared by every route this module registers, for active-nav checks. */
    public function routeNamePrefix(): string
    {
        return $this->value.'.';
    }

    /** Tailwind classes for a soft badge/tile in this module's accent color. */
    public function softClasses(): string
    {
        return match ($this) {
            self::Lms => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20',
            self::Cbt => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::Library => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
        };
    }

    /** Tailwind classes for a solid button/accent in this module's color. */
    public function solidClasses(): string
    {
        return match ($this) {
            self::Lms => 'bg-indigo-600 hover:bg-indigo-500 focus-visible:outline-indigo-600',
            self::Cbt => 'bg-emerald-600 hover:bg-emerald-500 focus-visible:outline-emerald-600',
            self::Library => 'bg-amber-600 hover:bg-amber-500 focus-visible:outline-amber-600',
        };
    }

    /** Tailwind text-color class for icons/accents in this module's color. */
    public function textClasses(): string
    {
        return match ($this) {
            self::Lms => 'text-indigo-600',
            self::Cbt => 'text-emerald-600',
            self::Library => 'text-amber-600',
        };
    }
}
