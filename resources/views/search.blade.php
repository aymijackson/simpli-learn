<x-app-layout :title="$term !== '' ? 'Search: '.$term : 'Search'">
    <form method="GET" action="{{ route('tenant.search') }}" class="mx-auto mb-10 max-w-2xl">
        <label class="relative block">
            <span class="sr-only">Search</span>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-5 h-5 w-5 -translate-y-1/2 text-slate-400" />
            <input type="search" name="q" value="{{ $term }}" autofocus
                   placeholder="Search courses, exams, books and more"
                   class="block w-full rounded-full border-0 bg-white py-4 pr-32 pl-13 text-base text-slate-900 shadow-sm ring-1 ring-slate-300 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-brand-600">
            <button type="submit" class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Search</button>
        </label>
    </form>

    @if ($term === '')
        <x-empty-state icon="search" title="What would you like to learn?" description="Search across everything available to you: courses, exams, and the digital library." />
    @elseif ($total === 0)
        <x-empty-state icon="search" :title="'No results for “'.$term.'”'" description="Check the spelling, or try a broader word." />
    @else
        <p class="mb-8 text-sm text-slate-500">{{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }} for <span class="font-semibold text-slate-900">&ldquo;{{ $term }}&rdquo;</span></p>

        <div class="space-y-12">
            @if ($courses->isNotEmpty())
                <section>
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900"><x-icon name="academic-cap" class="text-indigo-600" /> Courses <span class="text-sm font-medium text-slate-400">{{ $courses->count() }}</span></h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($courses as $course)
                            <x-course-card :course="$course" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($exams->isNotEmpty())
                <section>
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900"><x-icon name="clipboard-check" class="text-emerald-600" /> Exams <span class="text-sm font-medium text-slate-400">{{ $exams->count() }}</span></h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($exams as $exam)
                            <x-exam-card :exam="$exam" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($resources->isNotEmpty())
                <section>
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900"><x-icon name="book-open" class="text-amber-600" /> Library <span class="text-sm font-medium text-slate-400">{{ $resources->count() }}</span></h2>
                    <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach ($resources as $resource)
                            <x-resource-card :resource="$resource" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif
</x-app-layout>
