<x-app-layout title="My Favorites">
    <x-page-header title="My favorites" subtitle="Resources you've saved for later." />

    @if ($favorites->isEmpty())
        <x-empty-state title="No favorites yet" description="Browse the library and tap the favorite button on anything you want to save." />
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($favorites as $favorite)
                <a href="{{ route('library.resources.show', $favorite->resource) }}" class="group block">
                    <x-card class="h-full transition hover:shadow-md hover:ring-slate-300">
                        <h3 class="text-base font-semibold text-slate-900">{{ $favorite->resource->title }}</h3>
                        @if ($favorite->resource->author)
                            <p class="mt-1 text-sm text-slate-500">{{ $favorite->resource->author }}</p>
                        @endif
                        <x-badge color="amber" class="mt-4">{{ $favorite->resource->category }}</x-badge>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
