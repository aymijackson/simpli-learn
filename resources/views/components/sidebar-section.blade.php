@props(['title' => null])

<div {{ $attributes->merge(['class' => 'space-y-0.5']) }}>
    @if ($title)
        <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $title }}</p>
    @endif
    {{ $slot }}
</div>
