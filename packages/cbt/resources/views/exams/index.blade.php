<x-app-layout title="Exams" flush>
    <section class="border-b border-slate-200 bg-gradient-to-br from-emerald-50 via-white to-sky-50">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-12 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold text-emerald-700">Testing</p>
                <h1 class="mt-1 font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Exams &amp; assessments</h1>
                <p class="mt-3 text-base text-slate-600">Timed, computer-based tests with instant, auto-graded results. See where you stand, then retake to improve.</p>

                <form method="GET" action="{{ route('cbt.exams.index') }}" class="mt-6 flex gap-2">
                    <label class="relative flex-1">
                        <span class="sr-only">Search exams</span>
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input type="search" name="q" value="{{ $search }}" placeholder="Search exams"
                               class="block w-full rounded-xl border-0 bg-white py-3 pr-4 pl-12 text-sm text-slate-900 shadow-sm ring-1 ring-slate-300 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-brand-600">
                    </label>
                    <x-button type="submit">Search</x-button>
                </form>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-slate-900">{{ $exams->count() }}</p>
                    <p class="text-xs text-slate-500">{{ $search !== '' ? 'Matches' : 'Available' }}</p>
                </div>
                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-slate-900">{{ $bestScores->count() }}</p>
                    <p class="text-xs text-slate-500">Attempted</p>
                </div>
                <div class="rounded-2xl bg-white px-5 py-4 ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-emerald-600">{{ $exams->filter(fn ($exam) => ($bestScores[$exam->id] ?? -1) >= $exam->pass_percentage)->count() }}</p>
                    <p class="text-xs text-slate-500">Passed</p>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($exams->isEmpty())
            <x-empty-state
                icon="clipboard-check"
                :title="$search !== '' ? 'No exams match “'.$search.'”' : 'No exams yet'"
                :description="$search !== '' ? 'Try a different search term.' : 'Published exams will show up here.'"
            />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($exams as $exam)
                    <x-exam-card :exam="$exam" :best="isset($bestScores[$exam->id]) ? (int) $bestScores[$exam->id] : null" />
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
