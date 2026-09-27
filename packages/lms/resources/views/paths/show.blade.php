@php
    $labels = [
        'passed' => ['Passed', 'green'],
        'in_progress' => ['In progress', 'brand'],
        'not_started' => ['Not started', 'slate'],
        'locked' => ['Locked', 'slate'],
    ];
@endphp

<x-app-layout :title="$path->title" flush>
    <section class="relative isolate overflow-hidden bg-ink-950 text-white">
        <div class="bg-dots absolute inset-0 -z-10"></div>
        <div class="absolute -top-32 right-10 -z-10 h-80 w-80 rounded-full bg-indigo-500/25 blur-3xl"></div>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav class="flex items-center gap-2 text-sm text-slate-400" aria-label="Breadcrumb">
                <a href="{{ route('lms.paths.index') }}" class="hover:text-white">Paths</a>
                <x-icon name="chevron-right" class="h-4 w-4" />
                <span class="truncate text-slate-300">{{ $path->title }}</span>
            </nav>
            <div class="mt-4 flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <h1 class="font-display text-3xl leading-tight font-bold tracking-tight sm:text-4xl">{{ $path->title }}</h1>
                    @if ($path->description)
                        <p class="mt-4 text-base leading-relaxed whitespace-pre-line text-slate-300">{{ $path->description }}</p>
                    @endif
                    <p class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-300">
                        <span class="inline-flex items-center gap-1.5"><x-icon name="academic-cap" class="h-4 w-4 text-indigo-300" /> {{ $steps->count() }} {{ \Illuminate\Support\Str::plural('course', $steps->count()) }}</span>
                        @if ($path->is_sequential)
                            <span class="inline-flex items-center gap-1.5"><x-icon name="flag" class="h-4 w-4 text-indigo-300" /> Taken in order</span>
                        @endif
                    </p>
                </div>
                <div class="w-full shrink-0 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10 lg:w-80">
                    <p class="text-sm text-slate-300">Your progress</p>
                    <p class="mt-1 text-3xl font-bold">{{ $percent }}%</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-emerald-400" style="width: {{ $percent }}%"></div></div>
                    @if ($percent === 100)
                        <p class="mt-4 flex items-center gap-2 text-sm font-semibold text-emerald-300"><x-icon name="check-circle" class="h-5 w-5" /> Path complete</p>
                    @elseif ($next)
                        <form method="POST" action="{{ route('lms.paths.start', $path) }}" class="mt-4">
                            @csrf
                            <x-button type="submit" class="w-full" icon-right="arrow-right">{{ $percent === 0 && $next['status'] === 'not_started' ? 'Start the path' : 'Continue' }}</x-button>
                        </form>
                        <p class="mt-2 truncate text-center text-xs text-slate-400">Next: {{ $next['course']->title }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="mb-4 text-xl font-bold text-slate-900">Courses</h2>
        <ol class="relative space-y-4 before:absolute before:top-4 before:bottom-4 before:left-5 before:w-px before:bg-slate-200">
            @foreach ($steps as $step)
                @php([$label, $color] = $labels[$step['status']])
                @php($course = $step['course'])
                <li class="relative flex gap-4">
                    <span class="z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold ring-4 ring-slate-50
                        {{ $step['status'] === 'passed' ? 'bg-emerald-500 text-white' : ($step['status'] === 'locked' ? 'bg-slate-200 text-slate-500' : 'bg-brand-600 text-white') }}">
                        @if ($step['status'] === 'passed')
                            <x-icon name="check" class="h-5 w-5" />
                        @elseif ($step['status'] === 'locked')
                            <x-icon name="lock" class="h-4 w-4" />
                        @else
                            {{ $loop->iteration }}
                        @endif
                    </span>
                    <a href="{{ route('lms.courses.show', $course) }}" class="block min-w-0 flex-1 rounded-2xl bg-white p-5 ring-1 ring-slate-200/80 transition hover:ring-brand-300">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $course->title }}</p>
                                @if ($course->subtitle)
                                    <p class="mt-0.5 line-clamp-2 text-sm text-slate-600">{{ $course->subtitle }}</p>
                                @endif
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $course->lessons_count }} {{ \Illuminate\Support\Str::plural('lesson', $course->lessons_count) }}
                                    @if ($course->pricing_policy->value === 'paid') &middot; {{ $course->currency }} {{ number_format($course->price, 2) }} @endif
                                </p>
                            </div>
                            <x-badge :color="$color">{{ $label }}</x-badge>
                        </div>
                        @if ($step['status'] === 'in_progress')
                            <x-progress class="mt-3" :value="$step['percent']" label />
                        @elseif ($step['status'] === 'locked')
                            <p class="mt-3 text-xs text-slate-500">Opens after you pass {{ $course->unmetPrerequisitesFor(auth()->user())->pluck('title')->join(', ', ' and ') }}.</p>
                        @endif
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
</x-app-layout>
