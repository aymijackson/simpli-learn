<?php

namespace Elibrary\Library\Enums;

enum LibraryPricingPolicy: string
{
    case Free = 'free';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free — anyone can borrow',
            self::Paid => 'Paid — purchase required to borrow',
        };
    }
}
