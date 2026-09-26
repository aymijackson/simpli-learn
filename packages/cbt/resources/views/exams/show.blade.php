@php
    $totalQuestions = $exam->questions()->count();
    $effectiveQuestions = $exam->questions_per_attempt ? min($exam->questions_per_attempt, $totalQuestions) : $totalQuestions;
    // Marked attempts only: an attempt awaiting essay marking has no score yet.
    $submittedAttempts = $attempts->filter->isSubmitted()->reject->isAwaitingMarking();
    $openAttempt = $attempts->first(fn ($attempt) => ! $attempt->isSubmitted());
    $best = $submittedAttempts->max('score');
@endphp

<x-app-layout :title="$exam->title" flush>
    <section class="relative isolate overflow-hidden bg-ink-950 text-white">
        <div class="bg-dots absolute inset-0 -z-10"></div>
        <div class="absolute -top-32 right-10 -z-10 h-80 w-80 rounded-full bg-emerald-500/20 blur-3xl"></div>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav class="flex items-center gap-2 text-sm text-slate-400">
                @if ($placement)
                    <a href="{{ $placement['url'] }}" class="truncate hover:text-white">{{ $placement['title'] }}</a>
                @else
                    <a href="{{ route('cbt.exams.index') }}" class="hover:text-white">Exams</a>
                @endif
                <x-icon name="chevron-right" class="h-4 w-4" />
                <span class="truncate text-slate-300">{{ $exam->title }}</span>
            </nav>

            <div class="mt-4 flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    @if ($placement)
                        <p class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-brand-500/20 px-3 py-1 text-xs font-semibold text-brand-100 ring-1 ring-brand-400/30">
                            <x-icon name="academic-cap" class="h-3.5 w-3.5" /> {{ $placement['checkpoint'] }} &middot; {{ $placement['title'] }}
                        </p>
                    @endif
                    <h1 class="font-display text-3xl leading-tight font-bold tracking-tight sm:text-4xl">{{ $exam->title }}</h1>
                    @if ($exam->description)
                        <p class="mt-4 text-base leading-relaxed text-slate-300">{{ \Illuminate\Support\Str::limit(trim(strip_tags($exam->description)), 220) }}</p>
                    @endif
                    @if ($exam->available_from || $exam->available_until || ! $exam->allow_retakes)
                        <div class="mt-5 flex flex-wrap gap-2">
                            @if ($exam->available_from)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs text-slate-200"><x-icon name="calendar" class="h-3.5 w-3.5" /> Opens {{ $exam->available_from->format('M j, Y g:ia') }}</span>
                            @endif
                            @if ($exam->available_until)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs text-slate-200"><x-icon name="calendar" class="h-3.5 w-3.5" /> Closes {{ $exam->available_until->format('M j, Y g:ia') }}</span>
                            @endif
                            @unless ($exam->allow_retakes)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs text-slate-200"><x-icon name="flag" class="h-3.5 w-3.5" /> One attempt only</span>
                            @endunless
                        </div>
                    @endif
                </div>

                <div class="shrink-0">
                    @if ($openAttempt)
                        <x-button :href="route('cbt.attempts.take', $openAttempt)" variant="white" size="lg" icon-right="arrow-right">Resume exam</x-button>
                    @elseif ($startBlockReason)
                        <span class="inline-flex items-center gap-2 rounded-xl bg-rose-500/15 px-4 py-3 text-sm font-medium text-rose-200 ring-1 ring-rose-400/30">
                            <x-icon name="lock" class="h-4 w-4" /> {{ $startBlockReason }}
                        </span>
                    @else
                        <form method="POST" action="{{ route('cbt.exams.start', $exam) }}">
                            @csrf
                            <x-button type="submit" variant="white" size="lg" icon="play">{{ $attempts->isEmpty() ? 'Start exam' : 'Retake exam' }}</x-button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat-card label="Questions" :value="$effectiveQuestions" icon="question" tone="sky"
                         :hint="$exam->questions_per_attempt && $exam->questions_per_attempt < $totalQuestions ? 'Picked at random, of '.$totalQuestions.' in the bank' : null" />
            <x-stat-card label="Time limit" :value="$exam->duration_minutes.' min'" icon="clock" tone="amber"
                         :hint="$exam->enforce_time_limit ? 'Auto-submits when time is up' : 'Guide only'" />
            <x-stat-card label="Pass mark" :value="$exam->pass_percentage.'%'" icon="flag" tone="emerald" />
            <x-stat-card label="Your attempts" :value="$attempts->count().($exam->max_attempts ? ' / '.$exam->max_attempts : '')" icon="chart-bar" tone="brand"
                         :hint="$best !== null ? 'Best score '.$best.'%' : null" />
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @if ($exam->description)
                    <x-card>
                        <h2 class="text-lg font-bold text-slate-900">About this exam</h2>
                        <div class="rich-text mt-3 text-sm text-slate-600">{!! $exam->description !!}</div>
                    </x-card>
                @endif

                @if ($submittedAttempts->isNotEmpty())
                    <div class="grid grid-cols-3 gap-4">
                        <x-card class="text-center">
                            <p class="text-2xl font-bold text-slate-900">{{ $submittedAttempts->min('score') }}%</p>
                            <p class="text-xs text-slate-500">Your lowest score</p>
                        </x-card>
                        <x-card class="text-center">
                            <p class="text-2xl font-bold text-emerald-600">{{ $submittedAttempts->max('score') }}%</p>
                            <p class="text-xs text-slate-500">Your highest score</p>
                        </x-card>
                        <x-card class="text-center">
                            <p class="text-2xl font-bold text-slate-900">{{ round($submittedAttempts->avg('score')) }}%</p>
                            <p class="text-xs text-slate-500">Your average score</p>
                        </x-card>
                    </div>
                @endif

                @if ($attempts->isNotEmpty())
                    <x-card :padded="false">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h2 class="text-base font-semibold text-slate-900">Attempt history</h2>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($attempts as $attempt)
                                <li class="flex items-center justify-between gap-4 px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full {{ ! $attempt->isSubmitted() ? 'bg-amber-50 text-amber-600' : ($attempt->passed() ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600') }}">
                                            <x-icon :name="! $attempt->isSubmitted() ? 'clock' : ($attempt->passed() ? 'check-circle' : 'x-mark')" class="h-4 w-4" />
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $attempt->started_at->format('M j, Y g:ia') }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $attempt->isSubmitted() ? 'Submitted '.$attempt->submitted_at->diffForHumans() : 'In progress' }}
                                            </p>
                                        </div>
                                    </div>
                                    @if ($attempt->isSubmitted())
                                        <a href="{{ route('cbt.attempts.result', $attempt) }}" class="flex items-center gap-3">
                                            @if ($attempt->isAwaitingMarking())
                                                <x-badge color="amber">Awaiting marking</x-badge>
                                            @else
                                                <x-badge :color="$attempt->passed() ? 'green' : 'red'">
                                                    {{ $attempt->score }}% &middot; {{ $attempt->passed() ? 'Passed' : 'Failed' }}
                                                </x-badge>
                                            @endif
                                            <x-icon name="chevron-right" class="h-4 w-4 text-slate-400" />
                                        </a>
                                    @else
                                        <x-button :href="route('cbt.attempts.take', $attempt)" size="sm">Resume</x-button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif
            </div>

            <aside>
                <x-card>
                    <h2 class="text-base font-semibold text-slate-900">Before you begin</h2>
                    <ul class="mt-4 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3"><x-icon name="clock" class="mt-0.5 h-4 w-4 text-slate-400" />
                            {{ $exam->enforce_time_limit ? 'You have '.$exam->duration_minutes.' minutes once you start; the exam submits itself when time runs out.' : 'Aim to finish in about '.$exam->duration_minutes.' minutes.' }}
                        </li>
                        <li class="flex gap-3"><x-icon name="list" class="mt-0.5 h-4 w-4 text-slate-400" />
                            {{ $exam->allow_backward_navigation ? 'You can move back and forth between questions and flag ones to revisit.' : 'Questions are answered in order; you can\'t go back to earlier questions.' }}
                        </li>
                        @if ($exam->randomize_questions || $exam->questions_per_attempt)
                            <li class="flex gap-3"><x-icon name="sparkles" class="mt-0.5 h-4 w-4 text-slate-400" /> Questions are shuffled for every attempt.</li>
                        @endif
                        @if ($exam->integrity_monitoring_enabled)
                            <li class="flex gap-3"><x-icon name="shield-check" class="mt-0.5 h-4 w-4 text-slate-400" /> This exam is monitored: leaving the exam window or copying text is recorded.</li>
                        @endif
                        <li class="flex gap-3"><x-icon name="bolt" class="mt-0.5 h-4 w-4 text-slate-400" /> Results are graded instantly when you submit.</li>
                    </ul>
                </x-card>
            </aside>
        </div>
    </div>
</x-app-layout>
