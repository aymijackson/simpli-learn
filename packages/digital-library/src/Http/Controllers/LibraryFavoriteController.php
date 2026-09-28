<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Models\LibraryFavorite;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryFavoriteController extends Controller
{
    public function index(Request $request): View
    {
        return view('library::favorites.index', [
            'favorites' => LibraryFavorite::with('resource')->where('user_id', $request->user()->id)
                ->whereHas('resource', fn ($query) => $query->where('is_published', true))->latest()->get(),
        ]);
    }

    public function store(Request $request, string $tenant, LibraryResource $resource): RedirectResponse
    {
        abort_unless($resource->is_published, 404);

        $resource->favorites()->firstOrCreate(['user_id' => $request->user()->id]);

        return back()->with('status', __('Added to favorites.'));
    }

    public function destroy(Request $request, string $tenant, LibraryResource $resource): RedirectResponse
    {
        $resource->favorites()->where('user_id', $request->user()->id)->delete();

        return back()->with('status', __('Removed from favorites.'));
    }
}
