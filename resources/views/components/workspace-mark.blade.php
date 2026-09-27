{{-- A workspace's logo, or its initial on a brand-coloured tile when it has none. --}}
@props(['tenant', 'size' => 'md', 'dark' => false])
@php
    $box = $size === 'sm' ? 'h-7 w-7 rounded-lg text-xs' : 'h-9 w-9 rounded-xl text-base';
    $logo = $tenant?->logoUrl();
@endphp
@if ($logo)
    <span {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center overflow-hidden {$box} ".($dark ? 'bg-white/95 p-1' : 'bg-white ring-1 ring-slate-200 p-0.5')]) }}>
        <img src="{{ $logo }}" alt="{{ $tenant->name }}" class="max-h-full max-w-full object-contain">
    </span>
@else
    <span {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center {$box} bg-gradient-to-br from-brand-500 to-brand-700 font-display font-bold text-white shadow-sm"]) }}>
        {{ $tenant?->initial() ?? '?' }}
    </span>
@endif
