<?php

namespace Elibrary\Lms\Enums;

enum CourseCertificatePolicy: string
{
    case None = 'none';
    case Free = 'free';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No certificate for this course',
            self::Free => 'Free — issued automatically on completion',
            self::Paid => 'Paid — no certificate until payment',
        };
    }
}
