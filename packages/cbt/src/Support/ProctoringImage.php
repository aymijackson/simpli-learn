<?php

namespace Elibrary\Cbt\Support;

use Illuminate\Http\UploadedFile;

/**
 * Checks that a webcam photo is really a small JPEG, PNG or WebP image
 * before it is stored — whether it arrives as a data: URL (the ID check,
 * posted with the start form) or as an uploaded file (photos during the
 * attempt).
 */
class ProctoringImage
{
    public const MAX_BYTES = 1_500_000;

    private const EXTENSIONS = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

    /** @return array{contents: string, extension: string}|null */
    public static function fromDataUrl(string $dataUrl): ?array
    {
        if (! preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $matches)) {
            return null;
        }

        $contents = base64_decode($matches[2], true);

        return $contents === false ? null : self::check($contents);
    }

    /** @return array{contents: string, extension: string}|null */
    public static function fromUpload(UploadedFile $file): ?array
    {
        $contents = @file_get_contents($file->getRealPath());

        return $contents === false ? null : self::check($contents);
    }

    /** @return array{contents: string, extension: string}|null */
    private static function check(string $contents): ?array
    {
        if ($contents === '' || strlen($contents) > self::MAX_BYTES) {
            return null;
        }

        $info = @getimagesizefromstring($contents);
        if (! $info || ! isset(self::EXTENSIONS[$info[2]]) || $info[0] < 16 || $info[1] < 16 || $info[0] > 4096 || $info[1] > 4096) {
            return null;
        }

        return ['contents' => $contents, 'extension' => self::EXTENSIONS[$info[2]]];
    }
}
