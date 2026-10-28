<?php

namespace Elibrary\Cbt\Enums;

enum IntegrityEventType: string
{
    case VisibilityHidden = 'visibility_hidden';
    case WindowBlur = 'window_blur';
    case Copy = 'copy';
    case Paste = 'paste';
    case FullscreenExit = 'fullscreen_exit';
    case CameraOff = 'camera_off';
    case MultipleScreens = 'multiple_screens';

    public function label(): string
    {
        return match ($this) {
            self::VisibilityHidden => 'Switched away from the exam tab',
            self::WindowBlur => 'Exam window lost focus',
            self::Copy => 'Copied text from the exam',
            self::Paste => 'Pasted text into the exam',
            self::FullscreenExit => 'Left full screen',
            self::CameraOff => 'Camera turned off or unavailable',
            self::MultipleScreens => 'More than one screen connected',
        };
    }
}
