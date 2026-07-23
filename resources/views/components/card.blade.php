@props(['padded' => true])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm '.($padded ? 'p-6' : '')]) }}>
    {{ $slot }}
</div>
