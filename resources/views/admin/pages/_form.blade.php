<x-input type="text" name="slug" label="Slug" value="{{ old('slug', $page->slug ?? '') }}" placeholder="home" required />
<p class="-mt-3 text-xs text-slate-500">The page with slug "home" powers the public homepage. Any other slug is reachable at /pages/{slug}.</p>

<x-input type="text" name="title" label="Title" value="{{ old('title', $page->title ?? '') }}" required />

<x-input type="text" name="subtitle" label="Subtitle (optional)" value="{{ old('subtitle', $page->subtitle ?? '') }}" />

<x-editor name="content" label="Content" :value="old('content', $page->content ?? '')" />

<label class="flex items-center gap-2 text-sm text-slate-600">
    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_published', $page->is_published ?? false))>
    Published
</label>
