@props(['seed' => '', 'icon' => 'academic-cap', 'label' => null])

{{-- Generated cover art for items that have no image of their own: the same
     title always gets the same gradient, so cards stay recognisable. --}}
@php
    $palettes = [
        ['from-violet-600', 'to-indigo-700'],
        ['from-sky-500', 'to-indigo-600'],
        ['from-emerald-500', 'to-teal-700'],
        ['from-rose-500', 'to-fuchsia-700'],
        ['from-amber-500', 'to-orange-600'],
        ['from-cyan-500', 'to-blue-700'],
        ['from-fuchsia-500', 'to-purple-700'],
        ['from-teal-500', 'to-emerald-700'],
        ['from-indigo-500', 'to-violet-800'],
        ['from-orange-500', 'to-rose-600'],
    ];
    $hash = crc32((string) $seed);
    [$from, $to] = $palettes[$hash % count($palettes)];
    $rotate = ['-rotate-12', 'rotate-6', 'rotate-12', '-rotate-6'][$hash % 4];
    $initials = collect(preg_split('/\s+/', trim(preg_replace('/[^\pL\pN\s]/u', '', (string) $seed))))
        ->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
@endphp

<div {{ $attributes->merge(['class' => "relative isolate overflow-hidden bg-gradient-to-br {$from} {$to}"]) }}>
    <div class="bg-dots absolute inset-0 -z-10"></div>
    <div class="absolute -right-6 -bottom-8 -z-10 {{ $rotate }} text-white/15">
        <x-icon :name="$icon" class="h-36 w-36" />
    </div>
    <div class="absolute top-4 left-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 font-display text-lg font-bold text-white ring-1 ring-white/25 backdrop-blur-sm">
        {{ $initials ?: '•' }}
    </div>
    @if ($label)
        <span class="absolute bottom-3 left-4 rounded-full bg-black/25 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white backdrop-blur-sm">{{ $label }}</span>
    @endif
    {{ $slot }}
</div>
