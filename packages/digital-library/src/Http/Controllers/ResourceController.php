<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $query = LibraryResource::query()->where('is_published', true);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->trim()->value()) {
            $query->where('category', $category);
        }

        return view('library::resources.index', [
            'resources' => $query->orderBy('title')->get(),
            'categories' => LibraryResource::query()->where('is_published', true)->distinct()->orderBy('category')->pluck('category'),
            'search' => $search ?? '',
            'activeCategory' => $category ?? '',
        ]);
    }

    public function show(string $tenant, LibraryResource $resource): View
    {
        abort_unless($resource->is_published, 404);

        return view('library::resources.show', [
            'resource' => $resource,
        ]);
    }
}
