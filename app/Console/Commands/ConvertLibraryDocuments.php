<?php

namespace App\Console\Commands;

use Elibrary\Library\Documents\DocumentConverter;
use Elibrary\Library\Enums\LibraryFileFormat;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Makes PDF copies of Word/Excel/PowerPoint library files that don't have
 * one yet (uploaded before conversion existed, or while LibreOffice wasn't
 * installed), so they can be viewed securely on the platform.
 */
class ConvertLibraryDocuments extends Command
{
    protected $signature = 'library:convert-documents';

    protected $description = 'Create PDF copies of library Office files so they can be viewed securely';

    public function handle(DocumentConverter $converter): int
    {
        if (! $converter->isAvailable()) {
            $this->error('LibreOffice was not found. Install it, or set LIBREOFFICE_PATH to its soffice binary.');

            return self::FAILURE;
        }

        $disk = Storage::disk('local');
        $done = $failed = 0;

        LibraryResourceFile::withoutGlobalScopes()
            ->where('format', LibraryFileFormat::Document->value)->whereNull('pdf_path')
            ->orderBy('id')
            ->each(function (LibraryResourceFile $file) use ($converter, $disk, &$done, &$failed) {
                $pdfPath = preg_replace('/\.[^.]+$/', '', $file->disk_path).'.pdf';

                if ($disk->exists($file->disk_path) && $converter->toPdf($disk->path($file->disk_path), $disk->path($pdfPath))) {
                    $file->forceFill(['pdf_path' => $pdfPath])->save();
                    $done++;
                } else {
                    $this->warn("  Couldn't convert #{$file->id} {$file->original_filename}");
                    $failed++;
                }
            });

        $this->info("Converted {$done}, failed {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
