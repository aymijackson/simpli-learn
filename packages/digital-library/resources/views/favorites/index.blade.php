<x-app-layout :title="__('My Favorites')">
    <x-page-header :title="__('My favorites')" :subtitle="__('Resources you\'ve saved for later.')" :eyebrow="__('Library')">
        <x-slot:actions>
            <x-button :href="route('library.checkouts.index')" variant="secondary" icon="bookmark">{{ __('My checkouts') }}</x-button>
            <x-button :href="route('library.resources.index')" variant="secondary" icon="book-open">{{ __('Browse library') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($favorites->isEmpty())
        <x-empty-state icon="heart" :title="__('No favorites yet')" :description="__('Browse the library and tap the favorite button on anything you want to save.')">
            <x-slot:action><x-button :href="route('library.resources.index')">{{ __('Browse the library') }}</x-button></x-slot:action>
        </x-empty-state>
    @else
        <div class="grid grid-cols-2 gap-x-6 gap-y-10 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($favorites as $favorite)
                <x-resource-card :resource="$favorite->resource" />
            @endforeach
        </div>
    @endif
</x-app-layout>
