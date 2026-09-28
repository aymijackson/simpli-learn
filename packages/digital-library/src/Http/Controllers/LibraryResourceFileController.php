<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Enums\LibraryFileAccessLevel;
use Elibrary\Library\Enums\LibraryFileFormat;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Every library file is served through here, from private storage, with
 * access re-checked on each request — there is no public URL to share, and
 * access ends the moment a loan is returned or a purchase is refunded.
 * "Open" files may also be downloaded; "secure" ones are only shown inline.
 */
class LibraryResourceFileController extends Controller
{
    public function download(Request $request, string $tenant, LibraryResource $resource, LibraryResourceFile $file): BinaryFileResponse
    {
        $this->authorizeAccess($request, $resource, $file);
        abort_unless($file->access_level === LibraryFileAccessLevel::Open, 404);

        return $this->respond($file, $file->disk_path, LibraryFileFormat::safeMimeFor($file->extension()), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    /**
     * Inline view, for the reader, the audio player and "View". Built as a
     * real BinaryFileResponse (not Storage::response(), which is a plain
     * fpassthru() with no Range support) so audio/video seeking works;
     * BinaryFileResponse::prepare() handles Range/206 automatically.
     */
    public function stream(Request $request, string $tenant, LibraryResource $resource, LibraryResourceFile $file): BinaryFileResponse
    {
        $this->authorizeAccess($request, $resource, $file);
        // Only what the platform can show; Office files through their PDF copy.
        abort_unless($file->viewer() !== null, 404);

        return $this->respond($file, $file->viewPath(), $file->viewMime(), ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function respond(LibraryResourceFile $file, string $path, string $mime, string $disposition): BinaryFileResponse
    {
        $disk = Storage::disk($file->disk());
        abort_unless($disk->exists($path), 404);

        $response = new BinaryFileResponse($disk->path($path));
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Even if opened directly, a file can never run scripts on this site.
        $response->headers->set('Content-Security-Policy', "sandbox; default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'");
        $name = str_replace(['/', '\\'], '', $file->original_filename ?? '') ?: basename($path);
        if ($path === $file->pdf_path) {
            $name = pathinfo($name, PATHINFO_FILENAME).'.pdf';
        }
        $response->setContentDisposition($disposition, $name, $this->asciiSafe($name));

        return $response;
    }

    /** Content-Disposition needs an ASCII fallback name. */
    private function asciiSafe(string $name): string
    {
        $safe = preg_replace('/[^\x20-\x7E]|[\/\\\\%"]/', '', $name);

        return $safe !== '' ? $safe : 'download';
    }

    private function authorizeAccess(Request $request, LibraryResource $resource, LibraryResourceFile $file): void
    {
        abort_unless($file->resource_id === $resource->id, 404);
        abort_unless($resource->is_published || $request->user()->isOwner(), 404);
        abort_unless($resource->isAccessibleTo($request->user()), 403);
    }
}
