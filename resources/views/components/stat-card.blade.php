@props(['label', 'value', 'icon' => null, 'hint' => null, 'tone' => 'brand', 'href' => null])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'group flex items-start gap-4 rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgb(15_23_42/0.04)] ring-1 ring-slate-200/80 transition'.($href ? ' hover:-translate-y-0.5 hover:shadow-md' : '')]) }}>
    @if ($icon)
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    @endif
    <div class="min-w-0">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        <p class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 text-xs text-slate-400">{{ $hint }}</p>
        @endif
    </div>
</{{ $tag }}>
