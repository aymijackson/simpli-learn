@props(['label' => null])

<div>
    @if ($label)
        <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <input {{ $attributes->merge(['class' => 'block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm']) }}>
    @error($attributes->get('name'))
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
