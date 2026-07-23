<?php

namespace Elibrary\Cbt\Enums;

enum NavigationMode: string
{
    case AllAtOnce = 'all_at_once';
    case OneAtATime = 'one_at_a_time';

    public function label(): string
    {
        return match ($this) {
            self::AllAtOnce => 'Show all questions on one page',
            self::OneAtATime => 'Navigate questions one at a time',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AllAtOnce => 'Every question is visible at once; the learner submits everything together.',
            self::OneAtATime => 'One question per page, with a review pane to track progress and flags.',
        };
    }
}
