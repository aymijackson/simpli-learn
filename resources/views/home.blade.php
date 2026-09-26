@php
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->before(' ');
    $inProgress = $myCourses->filter(fn ($course) => $course->progress < 100);
    $completed = $myCourses->filter(fn ($course) => $course->progress === 100);
    $passedCount = $recentAttempts->filter->passed()->count();
    $moduleIcons = ['lms' => 'academic-cap', 'cbt' => 'clipboard-check', 'library' => 'book-open'];
@endphp

<x-app-layout :title="$tenant->name" flush>
    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-ink-950">
        <div class="bg-dots absolute inset-0 -z-10"></div>
        <div class="absolute -top-24 -right-24 -z-10 h-96 w-96 rounded-full bg-brand-600/30 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 -z-10 h-72 w-72 rounded-full bg-sky-500/20 blur-3xl"></div>

        <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-12 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8 lg:py-16">
            <div class="max-w-2xl">
                <p class="text-sm font-medium text-brand-300">{{ $tenant->name }}</p>
                <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Welcome back, {{ $firstName }}</h1>
                <p class="mt-3 text-base text-slate-300">
                    @if ($inProgress->isNotEmpty())
                        You have {{ $inProgress->count() }} {{ \Illuminate\Support\Str::plural('course', $inProgress->count()) }} in progress. Keep the momentum going.
                    @else
                        Pick something new to learn today &mdash; every lesson counts.
                    @endif
                </p>
            </div>

            @if ($enabledModules->isNotEmpty())
                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                    @php
                        $heroStats = [];
                        if ($enabledModules->contains(fn ($m) => $m->module->value === 'lms')) {
                            $heroStats[] = ['value' => $myCourses->count(), 'label' => 'Enrolled'];
                            $heroStats[] = ['value' => $completed->count(), 'label' => 'Completed'];
                        }
                        if ($enabledModules->contains(fn ($m) => $m->module->value === 'cbt')) {
                            $heroStats[] = ['value' => $passedCount, 'label' => 'Exams passed'];
                        }
                        if ($enabledModules->contains(fn ($m) => $m->module->value === 'library')) {
                            $heroStats[] = ['value' => $checkouts->count(), 'label' => 'On loan'];
                        }
                        $heroStats = array_slice($heroStats, 0, 3);
                    @endphp
                    @foreach ($heroStats as $stat)
                        <div class="rounded-2xl bg-white/5 px-4 py-3 text-center ring-1 ring-white/10 backdrop-blur-sm sm:px-6">
                            <p class="text-2xl font-bold text-white">{{ $stat['value'] }}</p>
                            <p class="text-xs text-slate-400">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-14 px-4 py-10 sm:px-6 lg:px-8">
        @if ($enabledModules->isEmpty())
            <x-empty-state
                icon="stack"
                title="No modules are enabled yet"
                description="Contact your administrator to enable Learning, CBT, or Library access for this organization."
            />
        @endif

        {{-- Resume unfinished exams first: they're on a clock. --}}
        @if ($openAttempts->isNotEmpty())
            <section>
                @foreach ($openAttempts as $attempt)
                    <div class="flex flex-col gap-4 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><x-icon name="clock" /></span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">You have an unfinished attempt at {{ $attempt->exam->title }}</p>
                                <p class="text-xs text-slate-600">Started {{ $attempt->started_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <x-button :href="route('cbt.attempts.take', $attempt)" variant="dark" icon-right="arrow-right">Resume exam</x-button>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Assigned courses come first: they have deadlines. --}}
        @if ($assignments->isNotEmpty())
            <section>
                <div class="mb-5">
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">Assigned to you</h2>
                    <p class="mt-1 text-sm text-slate-500">Courses {{ $tenant->name }} has asked you to complete.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($assignments as $assignment)
                        @php($overdue = $assignment->isOverdue())
                        <a href="{{ route('lms.courses.show', $assignment->course) }}"
                           class="group flex items-center gap-4 rounded-2xl bg-white p-4 ring-1 transition hover:shadow-lg {{ $overdue ? 'ring-rose-300' : 'ring-slate-200/80' }}">
                            <x-cover :seed="$assignment->course->title" icon="academic-cap" class="h-20 w-28 shrink-0 rounded-xl" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900 group-hover:text-brand-700">{{ $assignment->course->title }}</p>
                                <p class="mt-0.5 text-xs {{ $overdue ? 'font-semibold text-rose-600' : 'text-slate-500' }}">
                                    @if ($assignment->due_at)
                                        {{ $overdue ? 'Overdue — was due' : 'Due' }} {{ $assignment->due_at->format('D, M j') }}
                                        @unless ($overdue) ({{ $assignment->due_at->diffForHumans() }}) @endunless
                                    @else
                                        No due date
                                    @endif
                                </p>
                                <x-progress class="mt-2" :value="$assignment->progress" label />
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Continue learning --}}
        @if ($myCourses->isNotEmpty())
            <section>
                <div class="mb-5 flex items-end justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">Continue learning</h2>
                        <p class="mt-1 text-sm text-slate-500">Your enrolled courses, most recent first.</p>
                    </div>
                    <a href="{{ route('lms.courses.index') }}" class="hidden text-sm font-semibold text-brand-700 hover:text-brand-600 sm:block">Browse all courses &rarr;</a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($inProgress->concat($completed) as $course)
                        <x-course-card :course="$course" :progress="$course->progress" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Results and loans --}}
        @if ($recentAttempts->isNotEmpty() || $checkouts->isNotEmpty())
            <section class="grid gap-6 lg:grid-cols-2">
                @if ($recentAttempts->isNotEmpty())
                    <x-card :padded="false">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900"><x-icon name="chart-bar" class="h-5 w-5 text-emerald-600" /> Recent results</h2>
                            <a href="{{ route('cbt.exams.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-600">All exams</a>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($recentAttempts as $attempt)
                                <li>
                                    <a href="{{ route('cbt.attempts.result', $attempt) }}" class="flex items-center justify-between gap-4 px-6 py-3.5 hover:bg-slate-50">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-slate-900">{{ $attempt->exam->title }}</p>
                                            <p class="text-xs text-slate-500">{{ $attempt->submitted_at->diffForHumans() }}</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-3">
                                            <span class="text-sm font-bold {{ $attempt->passed() ? 'text-emerald-600' : 'text-rose-600' }}">{{ $attempt->score }}%</span>
                                            <x-badge :color="$attempt->passed() ? 'green' : 'red'">{{ $attempt->passed() ? 'Passed' : 'Failed' }}</x-badge>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif

                @if ($checkouts->isNotEmpty())
                    <x-card :padded="false">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900"><x-icon name="bookmark" class="h-5 w-5 text-amber-600" /> On loan</h2>
                            <a href="{{ route('library.checkouts.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-600">My checkouts</a>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($checkouts as $checkout)
                                <li>
                                    <a href="{{ route('library.resources.show', $checkout->resource) }}" class="flex items-center justify-between gap-4 px-6 py-3.5 hover:bg-slate-50">
                                        <p class="min-w-0 truncate text-sm font-medium text-slate-900">{{ $checkout->resource->title }}</p>
                                        @if ($checkout->isOverdue())
                                            <x-badge color="red">Overdue</x-badge>
                                        @elseif ($checkout->due_at)
                                            <span class="shrink-0 text-xs text-slate-500">Due {{ $checkout->due_at->format('M j') }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif
            </section>
        @endif

        {{-- Recommendations --}}
        @if ($suggestedCourses->isNotEmpty())
            <section>
                <div class="mb-5 flex items-end justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ $myCourses->isEmpty() ? 'Start learning today' : 'Recommended for you' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Fresh courses from {{ $tenant->name }}.</p>
                    </div>
                    <a href="{{ route('lms.courses.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">View all &rarr;</a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($suggestedCourses as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($suggestedExams->isNotEmpty())
            <section>
                <div class="mb-5 flex items-end justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">Test your knowledge</h2>
                        <p class="mt-1 text-sm text-slate-500">Timed exams with instant, auto-graded results.</p>
                    </div>
                    <a href="{{ route('cbt.exams.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">View all &rarr;</a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($suggestedExams as $exam)
                        <x-exam-card :exam="$exam" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($newResources->isNotEmpty())
            <section>
                <div class="mb-5 flex items-end justify-between">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">New in the library</h2>
                        <p class="mt-1 text-sm text-slate-500">Books, papers and study material to read online.</p>
                    </div>
                    <a href="{{ route('library.resources.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">Browse library &rarr;</a>
                </div>
                <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($newResources as $resource)
                        <x-resource-card :resource="$resource" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Explore by area --}}
        @if ($enabledModules->isNotEmpty())
            <section>
                <h2 class="mb-5 text-xl font-bold tracking-tight text-slate-900">Explore</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($enabledModules as $tenantModule)
                        @php($module = $tenantModule->module)
                        <a href="{{ route($module->routeName()) }}" class="group flex items-center gap-4 rounded-2xl bg-white p-5 ring-1 ring-slate-200/80 transition hover:shadow-lg hover:ring-slate-300">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $module->softClasses() }}">
                                <x-icon :name="$moduleIcons[$module->value]" class="h-6 w-6" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-base font-semibold text-slate-900">{{ $module->label() }}</span>
                                <span class="block text-sm text-slate-500">{{ $module->tagline() }}</span>
                            </span>
                            <x-icon name="arrow-right" class="h-5 w-5 text-slate-300 transition group-hover:translate-x-1 group-hover:text-brand-600" />
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
