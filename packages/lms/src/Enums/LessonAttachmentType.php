<?php

namespace Elibrary\Lms\Enums;

enum LessonAttachmentType: string
{
    case Video = 'video';
    case Audio = 'audio';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Audio => 'Audio',
            self::File => 'File / document',
        };
    }

    /** @return list<string> */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::Video => ['mp4', 'webm', 'mov'],
            self::Audio => ['mp3', 'wav', 'ogg'],
            self::File => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'txt'],
        };
    }
}
