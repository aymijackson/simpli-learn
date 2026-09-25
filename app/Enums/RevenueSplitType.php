<?php

namespace App\Enums;

enum RevenueSplitType: string
{
    case Manual = 'manual';
    case Percentage = 'percentage';
    case FlatFee = 'flat_fee';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual — reconcile off-system, no automatic fee calculation',
            self::Percentage => 'Percentage — a fixed percentage cut recorded per payment',
            self::FlatFee => 'Flat fee — a fixed amount recorded per payment',
        };
    }
}
