<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(): View
    {
        return view('library::manage.resources.index', [
            'resources' => LibraryResource::orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('library::manage.resources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        LibraryResource::create($this->validated($request));

        return redirect()->route('library.manage.resources.index')->with('status', 'Resource created.');
    }

    public function edit(string $tenant, LibraryResource $resource): View
    {
        return view('library::manage.resources.edit', ['resource' => $resource]);
    }

    public function update(Request $request, string $tenant, LibraryResource $resource): RedirectResponse
    {
        $resource->update($this->validated($request, $resource));

        return redirect()->route('library.manage.resources.index')->with('status', 'Resource updated.');
    }

    public function destroy(string $tenant, LibraryResource $resource): RedirectResponse
    {
        $resource->delete();

        return redirect()->route('library.manage.resources.index')->with('status', 'Resource deleted.');
    }

    private function validated(Request $request, ?LibraryResource $resource = null): array
    {
        $tenantId = app(Tenancy::class)->id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'alpha_dash', 'max:255',
                Rule::unique('library_resources', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($resource),
            ],
            'author' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'external_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $validated['is_published'] = $request->boolean('is_published');

        return $validated;
    }
}
