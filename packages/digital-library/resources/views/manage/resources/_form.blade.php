<x-input type="text" name="title" id="resource-title" label="Title" value="{{ old('title', $resource->title ?? '') }}" required autofocus />

<x-input type="text" name="slug" id="resource-slug" label="Slug" value="{{ old('slug', $resource->slug ?? '') }}" required />

<div class="grid gap-5 sm:grid-cols-2">
    <x-input type="text" name="author" label="Author (optional)" value="{{ old('author', $resource->author ?? '') }}" />
    <x-input type="text" name="category" label="Category" value="{{ old('category', $resource->category ?? '') }}" required />
</div>

<x-editor name="description" label="Description (optional)" :value="old('description', $resource->description ?? '')" />

<x-input type="url" name="external_url" label="External URL (optional)" value="{{ old('external_url', $resource->external_url ?? '') }}" placeholder="https://" />

<label class="flex items-center gap-2 text-sm text-slate-600">
    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_published', $resource->is_published ?? false))>
    Published (visible to members)
</label>

@push('scripts')
    <script>
        (function () {
            const titleField = document.getElementById('resource-title');
            const slugField = document.getElementById('resource-slug');
            let slugTouched = slugField.value.length > 0;

            const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

            titleField.addEventListener('input', () => {
                if (!slugTouched) {
                    slugField.value = slugify(titleField.value);
                }
            });

            slugField.addEventListener('input', () => {
                slugTouched = true;
            });
        })();
    </script>
@endpush
