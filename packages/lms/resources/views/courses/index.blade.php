@php
    $filtered = $search !== '' || $activePrice || $activeCategory || $activeLevel;
    $filterUrl = fn (array $changes) => route('lms.courses.index', array_filter(array_merge(
        ['q' => $search, 'price' => $activePrice, 'category' => $activeCategory, 'level' => $activeLevel, 'sort' => $activeSort === 'newest' ? null : $activeSort],
        $changes,
    )));
@endphp

<x-app-layout title="Courses" flush>
    <section class="border-b border-slate-200 bg-gradient-to-br from-indigo-50 via-white to-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold text-indigo-700">Learning</p>
            <h1 class="mt-1 font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Explore courses</h1>
            <p class="mt-3 max-w-2xl text-base text-slate-600">Structured lessons you can take at your own pace, with progress tracking{{ $totalPublished ? ' — '.$totalPublished.' '.\Illuminate\Support\Str::plural('course', $totalPublished).' available' : '' }}.</p>

            <form method="GET" action="{{ route('lms.courses.index') }}" class="mt-6 flex max-w-2xl gap-2">
                @if ($activePrice)<input type="hidden" name="price" value="{{ $activePrice }}">@endif
                @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                @if ($activeLevel)<input type="hidden" name="level" value="{{ $activeLevel }}">@endif
                @if ($activeSort !== 'newest')<input type="hidden" name="sort" value="{{ $activeSort }}">@endif
                <label class="relative flex-1">
                    <span class="sr-only">Search courses</span>
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search courses"
                           class="block w-full rounded-xl border-0 bg-white py-3 pr-4 pl-12 text-sm text-slate-900 shadow-sm ring-1 ring-slate-300 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-brand-600">
                </label>
                <x-button type="submit">Search</x-button>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($categories->isNotEmpty())
            <div class="scrollbar-none -mx-1 mb-5 flex gap-2 overflow-x-auto px-1 pb-1">
                <a href="{{ $filterUrl(['category' => null]) }}"
                   class="shrink-0 rounded-lg px-3.5 py-2 text-sm font-medium {{ $activeCategory === '' ? 'bg-brand-600 text-white' : 'bg-white text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50' }}">All categories</a>
                @foreach ($categories as $category)
                    <a href="{{ $filterUrl(['category' => $category]) }}"
                       class="shrink-0 rounded-lg px-3.5 py-2 text-sm font-medium {{ $activeCategory === $category ? 'bg-brand-600 text-white' : 'bg-white text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50' }}">{{ $category }}</a>
                @endforeach
            </div>
        @endif

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['' => 'All courses', 'free' => 'Free', 'paid' => 'Paid'] as $value => $label)
                    <a href="{{ $filterUrl(['price' => $value ?: null]) }}"
                       class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $activePrice === $value ? 'bg-ink-900 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300 ring-inset hover:bg-slate-50' }}">{{ $label }}</a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('lms.courses.index') }}" class="flex items-center gap-2 text-sm">
                @if ($search)<input type="hidden" name="q" value="{{ $search }}">@endif
                @if ($activePrice)<input type="hidden" name="price" value="{{ $activePrice }}">@endif
                @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                <label for="course-level" class="sr-only">Level</label>
                <select id="course-level" name="level" onchange="this.form.submit()"
                        class="rounded-lg border-0 bg-white py-1.5 pr-8 pl-3 text-sm font-medium text-slate-900 ring-1 ring-slate-300 ring-inset focus:ring-2 focus:ring-brand-600">
                    <option value="">Any level</option>
                    @foreach (\Elibrary\Lms\Enums\CourseLevel::cases() as $level)
                        <option value="{{ $level->value }}" @selected($activeLevel === $level->value)>{{ $level->label() }}</option>
                    @endforeach
                </select>
                <label for="course-sort" class="text-slate-500">Sort by</label>
                <select id="course-sort" name="sort" onchange="this.form.submit()"
                        class="rounded-lg border-0 bg-white py-1.5 pr-8 pl-3 text-sm font-medium text-slate-900 ring-1 ring-slate-300 ring-inset focus:ring-2 focus:ring-brand-600">
                    <option value="newest" @selected($activeSort === 'newest')>Newest</option>
                    <option value="popular" @selected($activeSort === 'popular')>Most popular</option>
                    <option value="rating" @selected($activeSort === 'rating')>Top rated</option>
                    <option value="title" @selected($activeSort === 'title')>Title (A&ndash;Z)</option>
                </select>
            </form>
        </div>

        @if ($courses->isEmpty())
            <x-empty-state
                icon="academic-cap"
                :title="$filtered ? 'No courses match your filters' : 'No courses yet'"
                :description="$filtered ? 'Try a different search or clear the filters.' : 'Published courses will show up here.'"
            >
                @if ($filtered)
                    <x-slot:action><x-button :href="route('lms.courses.index')" variant="secondary">Clear filters</x-button></x-slot:action>
                @endif
            </x-empty-state>
        @else
            <p class="mb-4 text-sm text-slate-500">{{ $courses->count() }} {{ \Illuminate\Support\Str::plural('result', $courses->count()) }}{{ $search !== '' ? ' for “'.$search.'”' : '' }}</p>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($courses as $course)
                    <x-course-card :course="$course" :progress="$progress[$course->id] ?? null" />
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
