<?php

namespace App\Support;

/**
 * URLs for files in public/ with a cache-busting ?v= stamp from the file's
 * modification time. A file missing on the server (e.g. not yet copied to
 * the web root on a cPanel deploy) falls back to the app version instead of
 * breaking the page.
 */
class Asset
{
    public static function url(string $path): string
    {
        $file = public_path($path);
        $version = is_file($file) ? filemtime($file) : config('app.asset_version', '1');

        return asset($path).'?v='.$version;
    }
}
