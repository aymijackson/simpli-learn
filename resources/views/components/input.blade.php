@props(['label' => null])

@php
    $name = $attributes->get('name');
    // Every labelled field needs an id so its <label for> points at it.
    $fieldId = $attributes->get('id') ?? ($label || $name ? 'field-'.\Illuminate\Support\Str::slug((string) $name ?: 'input').'-'.\Illuminate\Support\Str::random(5) : null);
    $hasError = $name && $errors->has($name);
    // Password fields get a show/hide button so people can check what they typed.
    $isPassword = $attributes->get('type') === 'password';
@endphp

<div>
    @if ($label)
        <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    @if ($isPassword)<div class="relative">@endif
    <input {{ $attributes->merge(['class' => 'block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm'.($isPassword ? ' pr-11' : ''), 'id' => $fieldId]) }}
           @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif>
    @if ($isPassword)
        <x-password-toggle />
        </div>
    @endif
    @error($name)
        <p id="{{ $fieldId }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
