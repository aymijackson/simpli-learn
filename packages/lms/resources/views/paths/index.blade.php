<x-app-layout :title="__('Learning paths')" flush>
    <section class="border-b border-slate-200 bg-gradient-to-br from-indigo-50 via-white to-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold text-indigo-700">{{ __('Learning') }}</p>
            <h1 class="mt-1 font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ __('Learning paths') }}</h1>
            <p class="mt-3 max-w-2xl text-base text-slate-600">{{ __('Sets of courses to take one after another, from the first step to the finish line.') }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($paths->isEmpty())
            <x-empty-state icon="map" :title="__('No learning paths yet')" :description="__('Paths will appear here once they\'re published.')" />
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($paths as $row)
                    @php($path = $row['path'])
                    <a href="{{ route('lms.paths.show', $path) }}" class="group flex flex-col rounded-2xl bg-white p-6 ring-1 ring-slate-200/80 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="map" class="h-5 w-5" /></span>
                        <h2 class="mt-4 text-lg font-semibold text-slate-900 group-hover:text-brand-700">{{ $path->title }}</h2>
                        @if ($path->description)
                            <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $path->description }}</p>
                        @endif
                        <p class="mt-3 text-xs text-slate-500">
                            {{ $path->courses->count() }} {{ trans_choice('course|courses', $path->courses->count()) }}
                            @if ($path->is_sequential) {{ __('· taken in order') }} @endif
                        </p>
                        <div class="mt-auto pt-5">
                            @if ($row['percent'] > 0)
                                <x-progress :value="$row['percent']" :tone="$row['percent'] === 100 ? 'emerald' : 'brand'" label />
                            @else
                                <span class="text-sm font-semibold text-brand-700">{{ __('View path →') }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
