<?php

namespace Elibrary\Library\Documents;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Converts documents with LibreOffice in headless mode. Uses the binary in
 * LIBREOFFICE_PATH when set, otherwise looks for `soffice`/`libreoffice` on
 * the PATH (and the usual Windows install folder). Nothing is sent off the
 * server. When LibreOffice isn't installed, isAvailable() is false and
 * owners are asked to upload a PDF instead.
 */
class LibreOfficeConverter implements DocumentConverter
{
    private ?string $binary;

    public function __construct(?string $binary = null)
    {
        $this->binary = $binary ?: $this->find();
    }

    public function isAvailable(): bool
    {
        return $this->binary !== null;
    }

    public function toPdf(string $sourcePath, string $pdfPath): bool
    {
        if (! $this->binary || ! is_file($sourcePath)) {
            return false;
        }

        // Its own working folder and profile, so parallel conversions don't collide.
        $work = storage_path('app/private/tmp/convert-'.Str::random(16));
        File::ensureDirectoryExists($work.'/profile');

        try {
            $process = new Process([
                $this->binary, '--headless', '--norestore', '--nologo', '--nodefault',
                '-env:UserInstallation=file:///'.ltrim(str_replace('\\', '/', $work.'/profile'), '/'),
                '--convert-to', 'pdf', '--outdir', $work, $sourcePath,
            ]);
            $process->setTimeout(120);
            $process->run();

            $produced = $work.'/'.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf';
            if (! $process->isSuccessful() || ! is_file($produced) || filesize($produced) === 0) {
                Log::warning('Library document conversion failed.', ['file' => basename($sourcePath), 'error' => Str::limit($process->getErrorOutput(), 500)]);

                return false;
            }

            File::ensureDirectoryExists(dirname($pdfPath));

            return File::move($produced, $pdfPath);
        } catch (Throwable $e) {
            report($e);

            return false;
        } finally {
            File::deleteDirectory($work);
        }
    }

    private function find(): ?string
    {
        $configured = config('services.libreoffice.path');
        if ($configured && is_file($configured)) {
            return $configured;
        }

        $finder = new ExecutableFinder;
        foreach (['soffice', 'libreoffice'] as $name) {
            if ($found = $finder->find($name)) {
                return $found;
            }
        }

        foreach (['C:\\Program Files\\LibreOffice\\program\\soffice.exe', '/usr/bin/soffice', '/usr/lib/libreoffice/program/soffice', '/opt/libreoffice/program/soffice'] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
