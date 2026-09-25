<?php

namespace Elibrary\Lms\Enums;

enum LessonAttachmentAccessLevel: string
{
    case Open = 'open';
    case Secure = 'secure';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open — directly downloadable',
            self::Secure => 'Secure — viewable on the platform only, no direct link or download',
        };
    }
}
