<?php

namespace Elibrary\Cbt\Enums;

enum AnswerType: string
{
    case Single = 'single';
    case Multiple = 'multiple';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single answer',
            self::Multiple => 'Multiple answers',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Single => 'Exactly one option can be marked correct; learners pick one.',
            self::Multiple => 'More than one option can be marked correct; learners can pick several.',
        };
    }
}
