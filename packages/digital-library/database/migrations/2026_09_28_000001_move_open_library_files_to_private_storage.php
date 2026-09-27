<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * "Open" library files used to sit on the public disk, reachable by anyone
 * holding the link. Move them to private storage, keeping the same relative
 * path, so they are only ever served through the app's access checks.
 * Safe to re-run: files already moved are skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        DB::table('library_resource_files')->where('access_level', 'open')->orderBy('id')
            ->each(function (object $file) use ($public, $private) {
                if ($private->exists($file->disk_path) || ! $public->exists($file->disk_path)) {
                    return;
                }

                $stream = $public->readStream($file->disk_path);
                $moved = $private->writeStream($file->disk_path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if ($moved && $private->exists($file->disk_path)) {
                    $public->delete($file->disk_path);
                } else {
                    Log::warning("Could not move library file #{$file->id} ({$file->disk_path}) to private storage.");
                }
            });
    }

    public function down(): void
    {
        // Deliberately one-way: putting files back on the public disk would
        // re-open the leak this fixes.
    }
};
