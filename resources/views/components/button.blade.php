@props(['href' => null, 'variant' => 'primary', 'type' => 'submit', 'size' => 'md', 'icon' => null, 'iconRight' => null])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-sm hover:bg-brand-700 focus-visible:outline-brand-600',
        'secondary' => 'bg-white text-slate-800 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus-visible:outline-brand-600',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-500 focus-visible:outline-red-600',
        'ghost' => 'text-slate-700 hover:bg-slate-100 focus-visible:outline-brand-600',
        'dark' => 'bg-ink-900 text-white shadow-sm hover:bg-ink-800 focus-visible:outline-ink-900',
        'white' => 'bg-white text-ink-900 shadow-sm hover:bg-brand-50 focus-visible:outline-white',
        'outline-white' => 'text-white ring-1 ring-inset ring-white/40 hover:bg-white/10 focus-visible:outline-white',
    ];

    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
        @if ($iconRight)<x-icon :name="$iconRight" class="h-4 w-4" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
        @if ($iconRight)<x-icon :name="$iconRight" class="h-4 w-4" />@endif
    </button>
@endif
