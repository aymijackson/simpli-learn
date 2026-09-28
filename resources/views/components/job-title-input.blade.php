{{-- A job-title field that suggests the workspace's existing titles as you
     type (native <datalist>, no script), so people pick "Accountant" rather
     than creating "accountant " again. Matching is also case- and
     space-insensitive on save. --}}
@props(['name' => 'job_title', 'value' => null, 'label' => null, 'hint' => null])

@php
    $listId = 'job-titles-'.\Illuminate\Support\Str::random(6);
    $titles = \App\Models\JobTitle::query()->orderBy('name')->pluck('name');
@endphp

<div>
    <x-input type="text" :name="$name" :label="$label ?? __('Job title')" :value="old($name, $value)" :list="$listId" autocomplete="off" maxlength="120" />
    <datalist id="{{ $listId }}">
        @foreach ($titles as $title)
            <option value="{{ $title }}"></option>
        @endforeach
    </datalist>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
