<x-app-layout title="Digital Library">
    <x-page-header title="Digital Library" subtitle="A searchable catalog of study resources.">
        <x-slot:actions>
            <x-button :href="route('library.favorites.index')" variant="secondary">My favorites</x-button>
            <x-button :href="route('library.checkouts.index')" variant="secondary">My checkouts</x-button>
            @if (auth()->user()->isOwner())
                <x-button :href="route('library.manage.resources.index')" variant="secondary">Manage resources</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('library.resources.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            type="text"
            name="q"
            value="{{ $search }}"
            placeholder="Search by title, author, publisher, ISBN&hellip;"
            class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:max-w-xs sm:text-sm"
        >
        @if ($activeCategory)
            <input type="hidden" name="category" value="{{ $activeCategory }}">
        @endif
        @if ($activeTag)
            <input type="hidden" name="tag" value="{{ $activeTag }}">
        @endif
        <select name="sort" onchange="this.form.submit()" class="block rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
            <option value="title" @selected($activeSort === 'title')>Title (A&ndash;Z)</option>
            <option value="newest" @selected($activeSort === 'newest')>Newest</option>
            <option value="publication_year" @selected($activeSort === 'publication_year')>Publication year</option>
        </select>
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>

    @if ($categories->isNotEmpty())
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('library.resources.index', array_filter(['q' => $search, 'tag' => $activeTag])) }}"
               class="rounded-full px-3 py-1 text-sm font-medium {{ $activeCategory === '' ? \App\Enums\Module::Library->softClasses() : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('library.resources.index', array_filter(['q' => $search, 'category' => $category, 'tag' => $activeTag])) }}"
                   class="rounded-full px-3 py-1 text-sm font-medium {{ $activeCategory === $category ? \App\Enums\Module::Library->softClasses() : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $category }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($tags->isNotEmpty())
        <div class="mb-8 flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                <a href="{{ route('library.resources.index', array_filter(['q' => $search, 'category' => $activeCategory, 'tag' => $activeTag === $tag->slug ? null : $tag->slug])) }}"
                   class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $activeTag === $tag->slug ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-500 ring-1 ring-inset ring-slate-200 hover:bg-slate-100' }}">
                    #{{ $tag->name }}
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
                        @if ($resource->cover_image_path)
                            <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($resource->cover_image_path) }}" alt="" class="mb-4 h-28 w-20 rounded-lg object-cover ring-1 ring-slate-200">
                        @else
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ \App\Enums\Module::Library->softClasses() }}">
                                <x-module-icon module="library" class="h-6 w-6" />
                            </div>
                        @endif
                        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $resource->title }}</h3>
                        @if ($resource->author)
                            <p class="mt-1 text-sm text-slate-500">{{ $resource->author }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap items-center gap-1.5">
                            <x-badge color="amber">{{ $resource->category }}</x-badge>
                            @foreach ($resource->tags as $tag)
                                <span class="rounded-full bg-slate-50 px-2 py-0.5 text-xs text-slate-500 ring-1 ring-inset ring-slate-200">#{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
