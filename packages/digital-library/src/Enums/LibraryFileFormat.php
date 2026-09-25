<?php

namespace Elibrary\Library\Enums;

enum LibraryFileFormat: string
{
    case Pdf = 'pdf';
    case Epub = 'epub';
    case Audio = 'audio';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Epub => 'EPUB',
            self::Audio => 'Audio',
            self::File => 'File / document',
        };
    }

    /** @return list<string> */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::Pdf => ['pdf'],
            self::Epub => ['epub'],
            self::Audio => ['mp3', 'wav', 'ogg'],
            self::File => ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'txt'],
        };
    }
}
