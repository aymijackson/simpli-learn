<?php

namespace Elibrary\Cbt\Enums;

enum ScoringMethod: string
{
    case AllOrNothing = 'all_or_nothing';
    case PartialCredit = 'partial_credit';

    public function label(): string
    {
        return match ($this) {
            self::AllOrNothing => 'All-or-nothing',
            self::PartialCredit => 'Partial credit',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AllOrNothing => 'Full credit only if every correct option is selected and no incorrect ones are.',
            self::PartialCredit => 'Credit scales with correct picks minus incorrect picks, floored at zero (the standard "right minus wrong" formula for multiple-response items).',
        };
    }
}
