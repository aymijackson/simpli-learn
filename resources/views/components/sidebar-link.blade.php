@props(['href', 'active' => false, 'icon' => null, 'badge' => null])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'group flex items-center gap-3 rounded-lg px-3 py-1.5 text-sm font-medium transition '.($active ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white')]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5 {{ $active ? 'text-brand-300' : 'text-slate-500 group-hover:text-slate-300' }}" />
    @endif
    <span class="flex-1 truncate">{{ $slot }}</span>
    @if ($badge)
        <span class="rounded-full bg-amber-400/15 px-2 py-0.5 text-xs font-semibold text-amber-300">{{ $badge }}</span>
    @endif
</a>
