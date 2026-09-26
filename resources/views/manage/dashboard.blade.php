@php
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->before(' ');
    $has = fn (string $value) => $enabled->contains(fn ($module) => $module->value === $value);
    $reviewLinks = [
        'lms' => ['label' => 'course payments', 'route' => 'lms.manage.course-purchases.index'],
        'cbt' => ['label' => 'certificate payments', 'route' => 'cbt.manage.certificate-payments.index'],
        'library' => ['label' => 'resource payments', 'route' => 'library.manage.resource-purchases.index'],
    ];
@endphp

<x-app-layout title="Dashboard">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ now()->format('l, F j') }}</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.75rem]">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ $firstName }}</h1>
            <p class="mt-1.5 text-sm text-slate-500">Here's what's happening in {{ $tenant->name }}.</p>
        </div>

        <details data-dropdown class="relative">
            <summary class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <x-icon name="plus" class="h-4 w-4" /> Create
                <x-icon name="chevron-down" class="details-chevron h-4 w-4 transition" />
            </summary>
            <div class="absolute right-0 z-10 mt-2 w-56 rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-slate-200">
                @if ($has('lms'))
                    <a href="{{ route('lms.manage.courses.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="academic-cap" class="h-4 w-4 text-indigo-500" /> New course</a>
                @endif
                @if ($has('cbt'))
                    <a href="{{ route('cbt.manage.exams.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="clipboard-check" class="h-4 w-4 text-emerald-500" /> New exam</a>
                @endif
                @if ($has('library'))
                    <a href="{{ route('library.manage.resources.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="book-open" class="h-4 w-4 text-amber-500" /> New resource</a>
                @endif
                <a href="{{ route('tenant.team.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="users" class="h-4 w-4 text-sky-500" /> Add a person</a>
            </div>
        </details>
    </div>

    {{-- Things that need the owner --}}
    @if (! empty($reviews) || ($stats['cbt']['toMark'] ?? 0) > 0)
        <div class="mb-8 space-y-3">
            @if (($stats['cbt']['toMark'] ?? 0) > 0)
                @php($toMark = $stats['cbt']['toMark'])
                <a href="{{ route('cbt.manage.marking.index') }}" class="flex items-center justify-between gap-4 rounded-xl bg-amber-50 px-5 py-4 ring-1 ring-amber-200 transition hover:bg-amber-100/70">
                    <span class="flex items-center gap-3 text-sm text-amber-900">
                        <x-icon name="pencil" class="h-5 w-5 text-amber-600" />
                        <span><strong>{{ $toMark }}</strong> exam {{ \Illuminate\Support\Str::plural('attempt', $toMark) }} with written answers {{ $toMark === 1 ? 'is' : 'are' }} waiting to be marked.</span>
                    </span>
                    <span class="shrink-0 text-sm font-semibold text-amber-800">Mark &rarr;</span>
                </a>
            @endif
            @foreach ($reviews as $module => $count)
                <a href="{{ route($reviewLinks[$module]['route']) }}" class="flex items-center justify-between gap-4 rounded-xl bg-amber-50 px-5 py-4 ring-1 ring-amber-200 transition hover:bg-amber-100/70">
                    <span class="flex items-center gap-3 text-sm text-amber-900">
                        <x-icon name="receipt" class="h-5 w-5 text-amber-600" />
                        <span><strong>{{ $count }}</strong> bank-transfer {{ \Illuminate\Support\Str::plural('payment', $count) }} for {{ $reviewLinks[$module]['label'] }} {{ $count === 1 ? 'is' : 'are' }} waiting for you to confirm.</span>
                    </span>
                    <span class="shrink-0 text-sm font-semibold text-amber-800">Review &rarr;</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="People" :value="number_format($stats['members'])" icon="users" tone="sky" :hint="$stats['newMembers'].' joined in the last 30 days'" :href="route('tenant.team.index')" />
        @isset($stats['lms'])
            <x-stat-card label="Enrollments" :value="number_format($stats['lms']['enrollments'])" icon="academic-cap" tone="indigo" :hint="$stats['lms']['recentEnrollments'].' in the last 30 days'" :href="route('lms.manage.courses.index')" />
        @endisset
        @isset($stats['cbt'])
            <x-stat-card label="Exam attempts" :value="number_format($stats['cbt']['attempts'])" icon="clipboard-check" tone="emerald" :hint="'Average score '.$stats['cbt']['averageScore'].'%'" :href="route('cbt.manage.analytics.index')" />
        @endisset
        @isset($stats['library'])
            <x-stat-card label="Books on loan" :value="number_format($stats['library']['onLoan'])" icon="book-open" tone="amber" :hint="$stats['library']['overdue'].' overdue'" :href="route('library.manage.analytics.index')" />
        @endisset
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        {{-- Module summaries --}}
        <div class="space-y-6 lg:col-span-2">
            @isset($stats['lms'])
                <x-card>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="academic-cap" /></span>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Learning</h2>
                                <p class="text-xs text-slate-500">Courses, lessons and enrollments</p>
                            </div>
                        </div>
                        <x-button :href="route('lms.manage.courses.index')" variant="secondary" size="sm">Manage</x-button>
                    </div>
                    <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div><dt class="text-xs text-slate-500">Courses</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['lms']['courses'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Published</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['lms']['published'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Enrollments</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['lms']['enrollments'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Paid sales</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['lms']['sales'] }}</dd></div>
                    </dl>
                </x-card>
            @endisset

            @isset($stats['cbt'])
                <x-card>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon name="clipboard-check" /></span>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Testing</h2>
                                <p class="text-xs text-slate-500">Exams, results and certificates</p>
                            </div>
                        </div>
                        <x-button :href="route('cbt.manage.exams.index')" variant="secondary" size="sm">Manage</x-button>
                    </div>
                    <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div><dt class="text-xs text-slate-500">Exams</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['cbt']['exams'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Attempts (30d)</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['cbt']['recentAttempts'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Average score</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['cbt']['averageScore'] }}%</dd></div>
                        <div><dt class="text-xs text-slate-500">Certificates</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['cbt']['certificates'] }}</dd></div>
                    </dl>
                    <x-progress class="mt-5" :value="$stats['cbt']['averageScore']" tone="emerald" />
                </x-card>
            @endisset

            @isset($stats['library'])
                <x-card>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="book-open" /></span>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Library</h2>
                                <p class="text-xs text-slate-500">Catalog, loans and reading</p>
                            </div>
                        </div>
                        <x-button :href="route('library.manage.resources.index')" variant="secondary" size="sm">Manage</x-button>
                    </div>
                    <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div><dt class="text-xs text-slate-500">Resources</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['library']['resources'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Published</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['library']['published'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">On loan</dt><dd class="mt-1 text-xl font-bold text-slate-900">{{ $stats['library']['onLoan'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Overdue</dt><dd class="mt-1 text-xl font-bold {{ $stats['library']['overdue'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $stats['library']['overdue'] }}</dd></div>
                    </dl>
                </x-card>
            @endisset

            @if ($enabled->isEmpty())
                <x-empty-state icon="stack" title="No modules are enabled yet" description="Ask the platform administrator to enable Learning, CBT, or Library for your workspace." />
            @endif
        </div>

        {{-- Activity feed --}}
        <x-card :padded="false" class="h-fit">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-900">Recent activity</h2>
            </div>
            @if ($activity->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-slate-500">Learner activity will appear here.</p>
            @else
                <ul class="space-y-1 p-3">
                    @foreach ($activity as $item)
                        @php($tones = ['indigo' => 'bg-indigo-50 text-indigo-600', 'emerald' => 'bg-emerald-50 text-emerald-600', 'rose' => 'bg-rose-50 text-rose-600', 'amber' => 'bg-amber-50 text-amber-600'])
                        <li class="flex gap-3 rounded-lg p-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $tones[$item['tone']] }}"><x-icon :name="$item['icon']" class="h-4 w-4" /></span>
                            <div class="min-w-0">
                                <p class="text-sm text-slate-700">{{ $item['text'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $item['at']->diffForHumans() }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-app-layout>
