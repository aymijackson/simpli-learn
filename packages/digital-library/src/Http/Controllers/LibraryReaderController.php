<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Enums\LibraryFileFormat;
use Elibrary\Library\Models\LibraryReadingProgress;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryReaderController extends Controller
{
    public function show(Request $request, string $tenant, LibraryResource $resource, LibraryResourceFile $file): View
    {
        abort_unless($file->resource_id === $resource->id, 404);
        abort_unless($resource->is_published, 404);
        abort_unless($resource->isAccessibleTo($request->user()), 403);
        abort_unless(in_array($file->format, [LibraryFileFormat::Pdf, LibraryFileFormat::Epub], true), 404);

        $progress = LibraryReadingProgress::where('resource_file_id', $file->id)
            ->where('user_id', $request->user()->id)
            ->first();

        $view = $file->format === LibraryFileFormat::Pdf ? 'library::reader.pdf' : 'library::reader.epub';

        return view($view, [
            'resource' => $resource,
            'file' => $file,
            'initialPosition' => $progress?->position,
        ]);
    }
}
