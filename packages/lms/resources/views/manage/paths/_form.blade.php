@php
    // Chosen courses first, in path order, then the rest alphabetically.
    $chosenIds = collect(old('course_ids', $chosen->all()))->map(fn ($id) => (int) $id);
    $oldPositions = old('positions', []);
    $ordered = $chosenIds->map(fn ($id) => $courses->firstWhere('id', $id))->filter()
        ->merge($courses->reject(fn ($course) => $chosenIds->contains($course->id)));
@endphp

<x-input type="text" name="title" id="path-title" label="Title" value="{{ old('title', $path->title ?? '') }}" required autofocus
         placeholder="e.g. Security onboarding" />

<x-input type="text" name="slug" id="path-slug" label="Slug" value="{{ old('slug', $path->slug ?? '') }}" required />

<x-textarea name="description" label="Description (optional)" rows="3"
            placeholder="Who it's for and what they'll be able to do at the end.">{{ old('description', $path->description ?? '') }}</x-textarea>

<div class="space-y-3">
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_published', $path->is_published ?? false))>
        Published (visible to learners under Paths)
    </label>
    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" name="is_sequential" value="1" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_sequential', $path->is_sequential ?? true))>
        <span>
            <span class="font-medium text-slate-700">Courses must be completed in order</span>
            <span class="block text-xs text-slate-500">Each course opens only after the one before it in this path is passed. This shows on each course as a requirement.</span>
        </span>
    </label>
</div>

<fieldset>
    <legend class="text-sm font-medium text-slate-700">Courses in this path</legend>
    <p class="mb-2 text-xs text-slate-500">Tick the courses to include and number them in the order learners should take them.</p>
    @if ($courses->isEmpty())
        <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200">Create some courses first.</p>
    @else
        <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto rounded-lg ring-1 ring-slate-200">
            @foreach ($ordered as $course)
                @php($position = $oldPositions[$course->id] ?? ($chosenIds->contains($course->id) ? $chosenIds->search($course->id) + 1 : ''))
                <div class="flex items-center gap-3 px-3 py-2">
                    <input type="number" name="positions[{{ $course->id }}]" value="{{ $position }}" min="0" max="999" aria-label="Order of {{ $course->title }}"
                           class="w-16 rounded-md border-0 px-2 py-1 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    <label class="flex min-w-0 flex-1 items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked($chosenIds->contains($course->id))>
                        <span class="truncate">{{ $course->title }}</span>
                        @unless ($course->is_published)
                            <x-badge color="slate">Draft</x-badge>
                        @endunless
                    </label>
                </div>
            @endforeach
        </div>
    @endif
    @error('course_ids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    @error('course_ids.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</fieldset>

@push('scripts')
    <script>
        (function () {
            const titleField = document.getElementById('path-title');
            const slugField = document.getElementById('path-slug');
            let slugTouched = slugField.value.length > 0;
            const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            titleField.addEventListener('input', () => { if (!slugTouched) slugField.value = slugify(titleField.value); });
            slugField.addEventListener('input', () => { slugTouched = true; });
        })();
    </script>
@endpush
