<?php

namespace Elibrary\Library\Enums;

/**
 * What kind of file an upload is, which decides how it is shown. Everything
 * except "file" can be viewed inside the platform after an access check;
 * Office documents are viewed through a PDF copy made at upload.
 */
enum LibraryFileFormat: string
{
    case Pdf = 'pdf';
    case Epub = 'epub';
    case Document = 'document';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Text = 'text';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Epub => 'EPUB',
            self::Document => 'Word, Excel or PowerPoint',
            self::Image => 'Image',
            self::Video => 'Video',
            self::Audio => 'Audio',
            self::Text => 'Text',
            self::File => 'Other file (download only)',
        };
    }

    /** Short badge text shown next to a file. */
    public function badge(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Epub => 'EPUB',
            self::Document => 'DOC',
            self::Image => 'IMG',
            self::Video => 'VIDEO',
            self::Audio => 'AUDIO',
            self::Text => 'TXT',
            self::File => 'FILE',
        };
    }

    /** @return list<string> */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::Pdf => ['pdf'],
            self::Epub => ['epub'],
            self::Document => ['doc', 'docx', 'odt', 'rtf', 'xls', 'xlsx', 'ods', 'csv', 'ppt', 'pptx', 'odp'],
            // No SVG: it can carry scripts.
            self::Image => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            self::Video => ['mp4', 'm4v', 'webm', 'mov', 'ogv'],
            self::Audio => ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'oga'],
            self::Text => ['txt', 'md'],
            self::File => ['zip'],
        };
    }

    public static function fromExtension(string $extension): ?self
    {
        $extension = strtolower($extension);

        foreach (self::cases() as $format) {
            if (in_array($extension, $format->allowedExtensions(), true)) {
                return $format;
            }
        }

        return null;
    }

    /** @return list<string> every extension any format accepts */
    public static function allExtensions(): array
    {
        return array_merge(...array_map(fn (self $format) => $format->allowedExtensions(), self::cases()));
    }

    /** Can people open it inside the platform (given a PDF copy for documents)? */
    public function isViewable(): bool
    {
        return $this !== self::File;
    }

    /** Needs a PDF copy made at upload before it can be viewed. */
    public function needsPdfCopy(): bool
    {
        return $this === self::Document;
    }

    /**
     * The Content-Type we serve for an extension. Chosen from this fixed
     * list — never from what the upload claims — so a disguised HTML file
     * can never be served as a web page.
     */
    public static function safeMimeFor(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'epub' => 'application/epub+zip',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4', 'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'ogv' => 'video/ogg',
            'mp3' => 'audio/mpeg',
            'm4a', 'aac' => 'audio/mp4',
            'wav' => 'audio/wav',
            'ogg', 'oga' => 'audio/ogg',
            'txt', 'md', 'csv' => 'text/plain; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }
}
