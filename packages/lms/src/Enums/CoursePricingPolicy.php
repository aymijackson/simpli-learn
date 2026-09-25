<?php

namespace Elibrary\Lms\Enums;

enum CoursePricingPolicy: string
{
    case Free = 'free';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free — anyone can enroll',
            self::Paid => 'Paid — payment required to enroll',
        };
    }
}
