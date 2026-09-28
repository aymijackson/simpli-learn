<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Models\LibraryReadingProgress;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Opens any viewable library file inside the platform, after the same
 * access check as the file itself: PDFs (and Office files, via their PDF
 * copy) and EPUBs in the reader, images, video and audio in a player, and
 * text inline.
 */
class LibraryReaderController extends Controller
{
    /** Longer text files are shown up to this many bytes. */
    private const TEXT_PREVIEW_BYTES = 1_000_000;

    public function show(Request $request, string $tenant, LibraryResource $resource, LibraryResourceFile $file): View
    {
        abort_unless($file->resource_id === $resource->id, 404);
        abort_unless($resource->is_published || $request->user()->isOwner(), 404);
        abort_unless($resource->isAccessibleTo($request->user()), 403);

        $viewer = $file->viewer();
        abort_unless($viewer !== null, 404);

        if (in_array($viewer, ['pdf', 'epub'], true)) {
            $progress = LibraryReadingProgress::where('resource_file_id', $file->id)
                ->where('user_id', $request->user()->id)
                ->first();

            return view("library::reader.{$viewer}", [
                'resource' => $resource,
                'file' => $file,
                'initialPosition' => $progress?->position,
            ]);
        }

        return view('library::reader.media', [
            'resource' => $resource,
            'file' => $file,
            'viewer' => $viewer,
            'text' => $viewer === 'text' ? $this->text($file) : null,
        ]);
    }

    /** @return array{body: string, truncated: bool} */
    private function text(LibraryResourceFile $file): array
    {
        $disk = Storage::disk($file->disk());
        abort_unless($disk->exists($file->disk_path), 404);

        $handle = $disk->readStream($file->disk_path);
        $body = (string) stream_get_contents($handle, self::TEXT_PREVIEW_BYTES + 1);
        fclose($handle);

        $truncated = strlen($body) > self::TEXT_PREVIEW_BYTES;
        $body = mb_convert_encoding(substr($body, 0, self::TEXT_PREVIEW_BYTES), 'UTF-8', 'UTF-8, Windows-1252');

        return ['body' => $body, 'truncated' => $truncated];
    }
}
