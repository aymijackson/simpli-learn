<?php

namespace Elibrary\Cbt\Enums;

enum IntegrityEventType: string
{
    case VisibilityHidden = 'visibility_hidden';
    case WindowBlur = 'window_blur';
    case Copy = 'copy';
    case Paste = 'paste';

    public function label(): string
    {
        return match ($this) {
            self::VisibilityHidden => 'Switched away from the exam tab',
            self::WindowBlur => 'Exam window lost focus',
            self::Copy => 'Copied text from the exam',
            self::Paste => 'Pasted text into the exam',
        };
    }
}
