@props(['color' => 'slate', 'icon' => null])

@php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-500/10',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'dark' => 'bg-ink-900 text-white ring-ink-900',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset '.($colors[$color] ?? $colors['slate'])]) }}>
    @if ($icon)<x-icon :name="$icon" class="h-3.5 w-3.5" />@endif
    {{ $slot }}
</span>
