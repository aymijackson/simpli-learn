<?php

namespace Elibrary\Cbt\Enums;

enum AnswerType: string
{
    case Single = 'single';
    case Multiple = 'multiple';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';
    case Essay = 'essay';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single answer',
            self::Multiple => 'Multiple answers',
            self::TrueFalse => 'True / false',
            self::ShortAnswer => 'Short answer (fill in the blank)',
            self::Essay => 'Essay (marked by hand)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Single => 'Exactly one option can be marked correct; learners pick one.',
            self::Multiple => 'More than one option can be marked correct; learners can pick several.',
            self::TrueFalse => 'Learners choose True or False.',
            self::ShortAnswer => 'Learners type a word or short phrase; it is marked correct if it matches any accepted answer (case and extra spaces are ignored).',
            self::Essay => 'Learners write a longer answer that an owner marks by hand. Results show "Awaiting marking" until then.',
        };
    }

    /** Learners pick from options (graded automatically against the correct ones). */
    public function usesOptions(): bool
    {
        return in_array($this, [self::Single, self::Multiple, self::TrueFalse], true);
    }

    /** Learners type their answer. */
    public function isWritten(): bool
    {
        return in_array($this, [self::ShortAnswer, self::Essay], true);
    }

    /** Only one option may be chosen / marked correct. */
    public function isSingleChoice(): bool
    {
        return in_array($this, [self::Single, self::TrueFalse], true);
    }
}
