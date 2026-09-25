<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\ResourceTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $resource = LibraryResource::create($this->validated($request));
        $this->syncTags($request, $resource);

        return redirect()->route('library.manage.resources.index')->with('status', 'Resource created.');
    }

    public function edit(string $tenant, LibraryResource $resource): View
    {
        return view('library::manage.resources.edit', [
            'resource' => $resource,
            'tagNames' => $resource->tags->pluck('name')->implode(', '),
        ]);
    }

    public function update(Request $request, string $tenant, LibraryResource $resource): RedirectResponse
    {
        $resource->update($this->validated($request, $resource));
        $this->syncTags($request, $resource);

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
            'isbn' => ['nullable', 'string', 'max:32'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:'.(now()->year + 1)],
            'language' => ['nullable', 'string', 'max:64'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'total_copies' => ['nullable', 'integer', 'min:1'],
            'checkout_duration_days' => ['nullable', 'integer', 'min:1'],
            'pricing_policy' => ['nullable', Rule::in(array_column(\Elibrary\Library\Enums\LibraryPricingPolicy::cases(), 'value'))],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['requires_checkout'] = $request->boolean('requires_checkout');
        $validated['checkout_duration_days'] = $validated['checkout_duration_days'] ?? 14;

        $validated['pricing_policy'] = $validated['pricing_policy'] ?? \Elibrary\Library\Enums\LibraryPricingPolicy::Free->value;
        if ($validated['pricing_policy'] === \Elibrary\Library\Enums\LibraryPricingPolicy::Free->value) {
            $validated['price'] = null;
            $validated['currency'] = null;
        }

        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            $directory = 'library-covers/'.$tenantId;
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $validated['cover_image_path'] = $file->storeAs($directory, $filename, 'public');
        }

        return $validated;
    }

    /**
     * Comma-separated tag names, parsed with firstOrCreate so re-using a name
     * across resources never duplicates the underlying tag row.
     */
    private function syncTags(Request $request, LibraryResource $resource): void
    {
        $tenantId = app(Tenancy::class)->id();

        $names = collect(explode(',', (string) $request->input('tags', '')))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique();

        $tagIds = $names->map(function (string $name) use ($tenantId) {
            $tag = ResourceTag::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => Str::slug($name)],
                ['name' => $name],
            );

            return $tag->id;
        });

        $resource->tags()->sync($tagIds);
    }
}
