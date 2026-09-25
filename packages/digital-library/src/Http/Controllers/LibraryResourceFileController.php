<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Enums\LibraryFileAccessLevel;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class LibraryResourceFileController extends Controller
{
    public function download(Request $request, string $tenant, LibraryResource $resource, LibraryResourceFile $file): RedirectResponse
    {
        $this->authorizeAccess($request, $resource, $file);
        abort_unless($file->access_level === LibraryFileAccessLevel::Open, 404);

        return redirect(Storage::disk('public')->url($file->disk_path));
    }

    /**
     * Re-checks access on every request. Built as a real BinaryFileResponse
     * (not Storage::response(), which is a plain fpassthru() with no Range
     * support at all — verified live: it never sends Accept-Ranges and
     * ignores Range headers entirely) so audio/video seeking actually works;
     * BinaryFileResponse::prepare() handles Range/206-partial-content
     * automatically.
     */
    public function stream(Request $request, string $tenant, LibraryResource $resource, LibraryResourceFile $file): BinaryFileResponse
    {
        $this->authorizeAccess($request, $resource, $file);
        abort_unless($file->access_level === LibraryFileAccessLevel::Secure, 404);

        $response = new BinaryFileResponse(Storage::disk('local')->path($file->disk_path));
        $response->headers->set('Content-Type', $file->mime_type ?? 'application/octet-stream');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file->original_filename ?? basename($file->disk_path));

        return $response;
    }

    private function authorizeAccess(Request $request, LibraryResource $resource, LibraryResourceFile $file): void
    {
        abort_unless($file->resource_id === $resource->id, 404);
        abort_unless($resource->is_published, 404);
        abort_unless($resource->isAccessibleTo($request->user()), 403);
    }
}
