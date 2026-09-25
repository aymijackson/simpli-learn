@props(['resource'])

{{-- Book-style catalog card for a Digital Library resource. --}}
<a href="{{ route('library.resources.show', $resource) }}" {{ $attributes->merge(['class' => 'group block']) }}>
    <div class="relative aspect-[3/4] overflow-hidden rounded-xl shadow-md ring-1 ring-slate-900/5 transition group-hover:-translate-y-1 group-hover:shadow-xl">
        @if ($resource->cover_image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($resource->cover_image_path) }}" alt="" loading="lazy" class="h-full w-full object-cover">
        @else
            <x-cover :seed="$resource->title" icon="book-open" class="h-full w-full">
                <p class="absolute inset-x-4 bottom-4 line-clamp-4 font-display text-lg leading-tight font-bold text-white">{{ $resource->title }}</p>
            </x-cover>
        @endif
        <div class="absolute inset-y-0 left-0 w-2 bg-gradient-to-r from-black/20 to-transparent"></div>
        @if ($resource->pricing_policy?->value === 'paid')
            <span class="absolute top-2 right-2 rounded-full bg-white/90 px-2 py-0.5 text-[11px] font-bold text-slate-900 shadow-sm">{{ $resource->currency }} {{ number_format($resource->price, 2) }}</span>
        @endif
    </div>
    <h3 class="mt-3 line-clamp-2 text-sm leading-snug font-semibold text-slate-900 group-hover:text-brand-700">{{ $resource->title }}</h3>
    @if ($resource->author)
        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $resource->author }}</p>
    @endif
    @if ($resource->category)
        <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-amber-700">{{ $resource->category }}</p>
    @endif
</a>
