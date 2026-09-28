<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Library\Documents\DocumentConverter;
use Elibrary\Library\Enums\LibraryFileAccessLevel;
use Elibrary\Library\Enums\LibraryFileFormat;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LibraryResourceFileController extends Controller
{
    public function store(Request $request, string $tenant, LibraryResource $resource, DocumentConverter $converter): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'format' => ['nullable', Rule::in(['auto', ...array_column(LibraryFileFormat::cases(), 'value')])],
            'access_level' => ['required', Rule::in(array_column(LibraryFileAccessLevel::cases(), 'value'))],
            'file' => ['required', 'file', 'max:512000'],
        ]);

        $upload = $request->file('file');
        $extension = strtolower($upload->getClientOriginalExtension());
        $accessLevel = LibraryFileAccessLevel::from($validated['access_level']);
        $format = $this->formatFor($extension, $validated['format'] ?? 'auto');

        // Private storage for every access level: files are only ever served
        // through the app, which re-checks access on each request.
        $name = Str::random(40);
        $path = $upload->storeAs('library-files/'.$resource->tenant_id, "{$name}.{$extension}", 'local');
        $pdfPath = null;

        if ($format->needsPdfCopy()) {
            $pdfPath = $this->makePdfCopy($converter, $path, $accessLevel);
        }

        $resource->files()->create([
            'title' => $validated['title'],
            'format' => $format->value,
            'access_level' => $accessLevel->value,
            'disk_path' => $path,
            'pdf_path' => $pdfPath,
            'mime_type' => LibraryFileFormat::safeMimeFor($extension),
            'original_filename' => $upload->getClientOriginalName(),
            'size' => $upload->getSize(),
            'position' => $resource->files()->count(),
        ]);

        $status = $format->needsPdfCopy() && ! $pdfPath
            ? 'File added. It can be downloaded, but not viewed on the platform — this server can’t convert Office files to PDF.'
            : 'File added.';

        return redirect()->route('library.manage.resources.edit', $resource)->with('status', $status);
    }

    public function destroy(string $tenant, LibraryResource $resource, LibraryResourceFile $file): RedirectResponse
    {
        abort_unless($file->resource_id === $resource->id, 404);

        Storage::disk($file->disk())->delete(array_filter([$file->disk_path, $file->pdf_path]));
        $file->delete();

        return redirect()->route('library.manage.resources.edit', $resource)->with('status', 'File deleted.');
    }

    /** The format from the file's extension, checked against the one chosen (if any). */
    private function formatFor(string $extension, string $chosen): LibraryFileFormat
    {
        $detected = LibraryFileFormat::fromExtension($extension);

        if (! $detected) {
            throw ValidationException::withMessages([
                'file' => 'That file type isn’t supported. Allowed: '.implode(', ', LibraryFileFormat::allExtensions()).'.',
            ]);
        }

        if ($chosen !== 'auto' && $chosen !== $detected->value) {
            $format = LibraryFileFormat::from($chosen);

            throw ValidationException::withMessages([
                'file' => "That file type isn't allowed for {$format->label()} files. Allowed: ".implode(', ', $format->allowedExtensions()).'.',
            ]);
        }

        return $detected;
    }

    /**
     * Office files are viewed through a PDF copy. A secure one that can't be
     * converted is refused (it could only ever be downloaded, which "secure"
     * forbids); an open one is kept as a download.
     */
    private function makePdfCopy(DocumentConverter $converter, string $path, LibraryFileAccessLevel $accessLevel): ?string
    {
        $disk = Storage::disk('local');
        $pdfPath = preg_replace('/\.[^.]+$/', '', $path).'.pdf';

        if ($converter->isAvailable() && $converter->toPdf($disk->path($path), $disk->path($pdfPath))) {
            return $pdfPath;
        }

        if ($accessLevel === LibraryFileAccessLevel::Secure) {
            $disk->delete($path);

            throw ValidationException::withMessages([
                'file' => $converter->isAvailable()
                    ? 'That document couldn’t be converted for secure viewing. Save it as a PDF and upload that instead.'
                    : 'This server can’t convert Word, Excel or PowerPoint files for secure viewing. Save the file as a PDF and upload that instead, or choose “Open” access to let people download it.',
            ]);
        }

        return null;
    }
}
