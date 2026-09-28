{{-- "Recommended for" on course and library resource forms: tick existing
     job titles, or (owners) type new ones, comma separated. People whose
     job title is ticked see the item under "Recommended for your role".
     Saved by App\Support\JobTitleSync. --}}
@props(['selected' => [], 'allowNew' => true])

@php
    $titles = \App\Models\JobTitle::query()->orderBy('name')->get();
    $selectedIds = collect(old('job_titles', collect($selected)->all()))->map(fn ($id) => (int) $id)->all();
@endphp

<fieldset>
    <input type="hidden" name="job_titles_submitted" value="1">
    <legend class="mb-1.5 block text-sm font-medium text-slate-700">Recommended for</legend>
    <p class="mb-2 text-xs text-slate-500">People with these job titles see this under “Recommended for your role”.</p>
    @if ($titles->isNotEmpty())
        <div class="mb-3 flex flex-wrap gap-2">
            @foreach ($titles as $title)
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-white px-3 py-1.5 text-sm text-slate-700 ring-1 ring-inset ring-slate-300 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-800 has-[:checked]:ring-brand-300">
                    <input type="checkbox" name="job_titles[]" value="{{ $title->id }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(in_array($title->id, $selectedIds, true))>
                    {{ $title->name }}
                </label>
            @endforeach
        </div>
    @elseif (! $allowNew)
        <p class="text-sm text-slate-500">No job titles yet — an owner can add them.</p>
    @endif
    @if ($allowNew)
        <x-input type="text" name="new_job_titles" :label="$titles->isEmpty() ? 'Job titles (comma separated)' : 'Add other job titles (comma separated)'" value="{{ old('new_job_titles') }}" placeholder="e.g. Accountant, Sales manager" />
    @endif
</fieldset>
