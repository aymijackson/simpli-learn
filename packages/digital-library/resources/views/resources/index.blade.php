@php
    $filterUrl = fn (array $changes) => route('library.resources.index', array_filter(array_merge(
        ['q' => $search, 'category' => $activeCategory, 'tag' => $activeTag, 'for' => $activeFor, 'sort' => $activeSort === 'title' ? null : $activeSort],
        $changes,
    )));
    $filtered = $search !== '' || $activeCategory !== '' || $activeTag !== '' || $activeFor;
@endphp

<x-app-layout :title="__('Digital Library')" flush>
    <section class="border-b border-slate-200 bg-gradient-to-br from-amber-50 via-white to-orange-50">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8">
            <div class="max-w-2xl flex-1">
                <p class="text-sm font-semibold text-amber-700">{{ __('Digital Library') }}</p>
                <h1 class="mt-1 font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ __('Find your next read') }}</h1>
                <p class="mt-3 text-base text-slate-600">{{ __('A searchable catalog of books, papers and study resources — read online or borrow.') }}</p>

                <form method="GET" action="{{ route('library.resources.index') }}" class="mt-6 flex gap-2">
                    @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                    @if ($activeTag)<input type="hidden" name="tag" value="{{ $activeTag }}">@endif
                    @if ($activeFor)<input type="hidden" name="for" value="role">@endif
                    @if ($activeSort !== 'title')<input type="hidden" name="sort" value="{{ $activeSort }}">@endif
                    <label class="relative flex-1">
                        <span class="sr-only">{{ __('Search the library') }}</span>
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-slate-500" />
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search by title, author, publisher, ISBN…') }}"
                               class="block w-full rounded-xl border-0 bg-white py-3 pr-4 pl-12 text-sm text-slate-900 shadow-sm ring-1 ring-slate-300 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-brand-600">
                    </label>
                    <x-button type="submit">{{ __('Search') }}</x-button>
                </form>
            </div>

            <div class="flex gap-2">
                <x-button :href="route('library.favorites.index')" variant="secondary" icon="heart">{{ __('My favorites') }}</x-button>
                <x-button :href="route('library.checkouts.index')" variant="secondary" icon="bookmark">{{ __('My checkouts') }}</x-button>
            </div>
        </div>
    </section>

    <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-10 sm:px-6 lg:flex-row lg:px-8">
        {{-- Filters --}}
        <aside class="lg:w-60 lg:shrink-0">
            @if ($categories->isNotEmpty())
                <div>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Categories') }}</p>
                    <div class="scrollbar-none -mx-1 flex gap-2 overflow-x-auto px-1 lg:flex-col lg:gap-0.5 lg:overflow-visible">
                        <a href="{{ $filterUrl(['category' => null]) }}"
                           class="shrink-0 rounded-lg px-3 py-2 text-sm font-medium {{ $activeCategory === '' ? 'bg-amber-100 text-amber-900' : 'text-slate-600 hover:bg-slate-100' }}">
                            {{ __('All categories') }}
                        </a>
                        @foreach ($categories as $category)
                            <a href="{{ $filterUrl(['category' => $category]) }}"
                               class="shrink-0 rounded-lg px-3 py-2 text-sm font-medium {{ $activeCategory === $category ? 'bg-amber-100 text-amber-900' : 'text-slate-600 hover:bg-slate-100' }}">
                                {{ $category }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($tags->isNotEmpty())
                <div class="mt-8">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Topics') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tags as $tag)
                            <a href="{{ $filterUrl(['tag' => $activeTag === $tag->slug ? null : $tag->slug]) }}"
                               class="rounded-full px-2.5 py-1 text-xs font-medium {{ $activeTag === $tag->slug ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-50' }}">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>

        {{-- Results --}}
        <div class="min-w-0 flex-1">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <p class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-500">
                    @if ($jobTitle)
                        <a href="{{ $filterUrl(['for' => $activeFor ? null : 'role', 'page' => null]) }}" @if ($activeFor) aria-current="true" @endif
                           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium transition {{ $activeFor ? 'bg-brand-600 text-white' : 'bg-brand-50 text-brand-800 ring-1 ring-brand-200 ring-inset hover:bg-brand-100' }}">
                            <x-icon name="sparkles" class="h-4 w-4" /> {{ __('For your role: :title', ['title' => $jobTitle->name]) }}
                        </a>
                    @endif
                    <span>{{ $resources->total() }} {{ trans_choice('resource|resources', $resources->total()) }}</span>
                    @if ($filtered)
                        &middot; <a href="{{ route('library.resources.index') }}" class="font-medium text-brand-700 hover:text-brand-600">{{ __('Clear filters') }}</a>
                    @endif
                </p>
                <form method="GET" action="{{ route('library.resources.index') }}" class="flex items-center gap-2 text-sm">
                    @if ($search)<input type="hidden" name="q" value="{{ $search }}">@endif
                    @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                    @if ($activeTag)<input type="hidden" name="tag" value="{{ $activeTag }}">@endif
                    <label for="library-sort" class="text-slate-500">{{ __('Sort by') }}</label>
                    <select id="library-sort" name="sort" onchange="this.form.submit()"
                            class="rounded-lg border-0 bg-white py-1.5 pr-8 pl-3 text-sm font-medium text-slate-900 ring-1 ring-slate-300 ring-inset focus:ring-2 focus:ring-brand-600">
                        <option value="title" @selected($activeSort === 'title')>{{ __('Title (A–Z)') }}</option>
                        <option value="newest" @selected($activeSort === 'newest')>{{ __('Newest') }}</option>
                        <option value="publication_year" @selected($activeSort === 'publication_year')>{{ __('Publication year') }}</option>
                    </select>
                </form>
            </div>

            @if ($resources->isEmpty())
                <x-empty-state icon="book-open" :title="__('No resources found')" :description="__('Try a different search term or category.')" />
            @else
                <div class="grid grid-cols-2 gap-x-6 gap-y-10 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($resources as $resource)
                        <x-resource-card :resource="$resource" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $resources->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
