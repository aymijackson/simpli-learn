<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Lms\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One search box across every enabled module's published catalog.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, Tenancy $tenancy): View
    {
        $tenant = $tenancy->current();
        $enabled = $tenant->tenantModules()->where('is_enabled', true)->get()->map(fn ($tenantModule) => $tenantModule->module);
        $term = $request->string('q')->trim()->limit(100, '')->value();
        $like = '%'.addcslashes($term, '%_\\').'%';

        $results = ['courses' => collect(), 'exams' => collect(), 'resources' => collect()];

        if ($term !== '') {
            if ($enabled->contains(Module::Lms)) {
                $results['courses'] = Course::where('is_published', true)
                    ->where(fn ($query) => $query->where('title', 'like', $like)
                        ->orWhere('subtitle', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('instructor_name', 'like', $like))
                    ->withCount(['lessons', 'reviews'])
                    ->withAvg('reviews', 'stars')
                    ->orderBy('title')
                    ->take(24)
                    ->get();
            }

            if ($enabled->contains(Module::Cbt)) {
                $results['exams'] = Exam::where('is_published', true)
                    ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('description', 'like', $like))
                    ->withCount('questions')
                    ->orderBy('title')
                    ->take(24)
                    ->get();
            }

            if ($enabled->contains(Module::Library)) {
                $results['resources'] = LibraryResource::where('is_published', true)
                    ->where(fn ($query) => $query->where('title', 'like', $like)
                        ->orWhere('author', 'like', $like)
                        ->orWhere('publisher', 'like', $like)
                        ->orWhere('isbn', 'like', $like)
                        ->orWhere('category', 'like', $like))
                    ->orderBy('title')
                    ->take(24)
                    ->get();
            }
        }

        return view('search', [
            'term' => $term,
            'enabled' => $enabled,
            'total' => collect($results)->sum(fn ($items) => $items->count()),
        ] + $results);
    }
}
