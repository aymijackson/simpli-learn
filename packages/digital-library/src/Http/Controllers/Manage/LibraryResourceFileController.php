<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Library\Enums\LibraryFileAccessLevel;
use Elibrary\Library\Enums\LibraryFileFormat;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LibraryResourceFileController extends Controller
{
    public function store(Request $request, string $tenant, LibraryResource $resource): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'format' => ['required', Rule::in(array_column(LibraryFileFormat::cases(), 'value'))],
            'access_level' => ['required', Rule::in(array_column(LibraryFileAccessLevel::cases(), 'value'))],
            'file' => ['required', 'file', 'max:512000'],
        ]);

        $format = LibraryFileFormat::from($validated['format']);
        $accessLevel = LibraryFileAccessLevel::from($validated['access_level']);
        $file = $request->file('file');

        abort_unless(
            in_array(strtolower($file->getClientOriginalExtension()), $format->allowedExtensions(), true),
            422,
            "That file type isn't allowed for {$format->label()} files."
        );

        $disk = $accessLevel === LibraryFileAccessLevel::Open ? 'public' : 'local';
        $directory = ($accessLevel === LibraryFileAccessLevel::Open ? 'library-files' : 'secure-library-files').'/'.$resource->tenant_id;
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, $disk);

        $resource->files()->create([
            'title' => $validated['title'],
            'format' => $format->value,
            'access_level' => $accessLevel->value,
            'disk_path' => $path,
            'mime_type' => $file->getMimeType(),
            'original_filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'position' => $resource->files()->count(),
        ]);

        return redirect()->route('library.manage.resources.edit', $resource)->with('status', 'File added.');
    }

    public function destroy(string $tenant, LibraryResource $resource, LibraryResourceFile $file): RedirectResponse
    {
        abort_unless($file->resource_id === $resource->id, 404);

        Storage::disk($file->disk())->delete($file->disk_path);
        $file->delete();

        return redirect()->route('library.manage.resources.edit', $resource)->with('status', 'File deleted.');
    }
}
