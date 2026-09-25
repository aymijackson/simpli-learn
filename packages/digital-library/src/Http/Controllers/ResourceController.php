<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\ResourceTag;
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
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('publisher', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->trim()->value()) {
            $query->where('category', $category);
        }

        if ($tag = $request->string('tag')->trim()->value()) {
            $query->whereHas('tags', fn ($inner) => $inner->where('slug', $tag));
        }

        $sort = $request->string('sort')->trim()->value() ?: 'title';
        match ($sort) {
            'newest' => $query->latest(),
            'publication_year' => $query->orderByDesc('publication_year'),
            default => $query->orderBy('title'),
        };

        return view('library::resources.index', [
            'resources' => $query->with('tags')->get(),
            'categories' => LibraryResource::query()->where('is_published', true)->distinct()->orderBy('category')->pluck('category'),
            'tags' => ResourceTag::query()->whereHas('resources', fn ($q) => $q->where('is_published', true))->orderBy('name')->get(),
            'search' => $search ?? '',
            'activeCategory' => $category ?? '',
            'activeTag' => $tag ?? '',
            'activeSort' => $sort,
        ]);
    }

    public function show(Request $request, string $tenant, LibraryResource $resource): View
    {
        abort_unless($resource->is_published, 404);

        $user = $request->user();

        return view('library::resources.show', [
            'resource' => $resource->load('tags', 'files'),
            'myCheckout' => $resource->checkouts()->where('user_id', $user->id)->whereNull('returned_at')->first(),
            'myHold' => $resource->holds()->where('user_id', $user->id)->whereNull('fulfilled_at')->first(),
            'myRating' => $resource->ratingFor($user),
            'isFavorited' => $resource->isFavoritedBy($user),
        ]);
    }
}
