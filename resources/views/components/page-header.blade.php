@props(['title', 'subtitle' => null, 'eyebrow' => null])

<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-brand-600">{{ $eyebrow }}</p>
        @endif
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.75rem]">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 max-w-2xl text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">{{ $actions }}</div>
    @endisset
</div>
