<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LibraryResourceRatingController extends Controller
{
    public function store(Request $request, string $tenant, LibraryResource $resource): RedirectResponse
    {
        $validated = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $resource->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        return redirect()->route('library.resources.show', $resource)->with('status', 'Thanks for your rating.');
    }
}
