<x-app-layout title="Digital Library">
    <x-page-header title="Digital Library" subtitle="A searchable catalog of study resources.">
        @if (auth()->user()->isOwner())
            <x-slot:actions>
                <x-button :href="route('library.manage.resources.index')" variant="secondary">Manage resources</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <form method="GET" action="{{ route('library.resources.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            type="text"
            name="q"
            value="{{ $search }}"
            placeholder="Search by title or author&hellip;"
            class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:max-w-xs sm:text-sm"
        >
        @if ($activeCategory)
            <input type="hidden" name="category" value="{{ $activeCategory }}">
        @endif
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>

    @if ($categories->isNotEmpty())
        <div class="mb-8 flex flex-wrap gap-2">
            <a href="{{ route('library.resources.index', array_filter(['q' => $search])) }}"
               class="rounded-full px-3 py-1 text-sm font-medium {{ $activeCategory === '' ? \App\Enums\Module::Library->softClasses() : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('library.resources.index', array_filter(['q' => $search, 'category' => $category])) }}"
                   class="rounded-full px-3 py-1 text-sm font-medium {{ $activeCategory === $category ? \App\Enums\Module::Library->softClasses() : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $category }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($resources->isEmpty())
        <x-empty-state title="No resources found" description="Try a different search term or category." />
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($resources as $resource)
                <a href="{{ route('library.resources.show', $resource) }}" class="group block">
                    <x-card class="h-full transition hover:shadow-md hover:ring-slate-300">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ \App\Enums\Module::Library->softClasses() }}">
                            <x-module-icon module="library" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $resource->title }}</h3>
                        @if ($resource->author)
                            <p class="mt-1 text-sm text-slate-500">{{ $resource->author }}</p>
                        @endif
                        <x-badge color="amber" class="mt-4">{{ $resource->category }}</x-badge>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
