@props(['title', 'description' => null, 'icon' => 'sparkles'])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-300 bg-white/60 px-6 py-16 text-center']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    <p class="mt-4 text-sm font-semibold text-slate-900">{{ $title }}</p>
    @if ($description)
        <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-6">{{ $action }}</div>
    @endisset
</div>
