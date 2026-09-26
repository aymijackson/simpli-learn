<?php

namespace Elibrary\Lms\Enums;

enum CourseLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case AllLevels = 'all_levels';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
            self::AllLevels => 'All levels',
        };
    }
}
