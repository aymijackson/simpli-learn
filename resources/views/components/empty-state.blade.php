@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border-2 border-dashed border-slate-200 py-16 px-6 text-center']) }}>
    <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-6">{{ $action }}</div>
    @endisset
</div>
