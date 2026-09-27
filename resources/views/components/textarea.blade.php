@props(['label' => null])

@php
    $name = $attributes->get('name');
    $fieldId = $attributes->get('id') ?? 'field-'.\Illuminate\Support\Str::slug((string) $name ?: 'text').'-'.\Illuminate\Support\Str::random(5);
    $hasError = $name && $errors->has($name);
@endphp

<div>
    @if ($label)
        <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <textarea {{ $attributes->merge(['class' => 'block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm', 'id' => $fieldId]) }}
              @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif>{{ $slot }}</textarea>
    @error($name)
        <p id="{{ $fieldId }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
