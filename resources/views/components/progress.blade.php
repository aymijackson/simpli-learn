@props(['value' => 0, 'tone' => 'brand', 'label' => false])

@php
    $value = max(0, min(100, (int) $value));
    $tones = [
        'brand' => 'bg-brand-600',
        'emerald' => 'bg-emerald-500',
        'indigo' => 'bg-indigo-600',
        'amber' => 'bg-amber-500',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full rounded-full {{ $value === 100 ? 'bg-emerald-500' : ($tones[$tone] ?? $tones['brand']) }}" style="width: {{ $value }}%"></div>
    </div>
    @if ($label)
        <span class="w-9 text-right text-xs font-semibold text-slate-600">{{ $value }}%</span>
    @endif
</div>
