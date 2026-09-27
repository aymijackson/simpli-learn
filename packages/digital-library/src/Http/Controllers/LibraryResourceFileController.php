<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Enums\LibraryFileAccessLevel;
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

        return $this->respond($file, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
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

        return $this->respond($file, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function respond(LibraryResourceFile $file, string $disposition): BinaryFileResponse
    {
        $disk = Storage::disk($file->disk());
        abort_unless($disk->exists($file->disk_path), 404);

        $response = new BinaryFileResponse($disk->path($file->disk_path));
        $response->headers->set('Content-Type', $file->mime_type ?? 'application/octet-stream');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $name = str_replace(['/', '\\'], '', $file->original_filename ?? '') ?: basename($file->disk_path);
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
