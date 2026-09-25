<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LibraryReadingProgressController extends Controller
{
    public function update(Request $request, string $tenant, LibraryResource $resource, LibraryResourceFile $file): Response
    {
        abort_unless($file->resource_id === $resource->id, 404);
        abort_unless($resource->isAccessibleTo($request->user()), 403);

        $validated = $request->validate([
            'position' => ['required', 'string', 'max:512'],
        ]);

        $file->readingProgress()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['position' => $validated['position']],
        );

        return response()->noContent();
    }
}
