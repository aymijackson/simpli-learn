@props(['padded' => true])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white shadow-[0_1px_2px_rgb(15_23_42/0.04)] ring-1 ring-slate-200/80 '.($padded ? 'p-6' : '')]) }}>
    {{ $slot }}
</div>
