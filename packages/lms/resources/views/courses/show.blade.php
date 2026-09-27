@php
    $user = auth()->user();
    $lessonState = $course->lessons->mapWithKeys(function ($lesson) use ($isEnrolled, $user) {
        $hasAccess = $isEnrolled || $lesson->is_preview;

        return [$lesson->id => [
            'hasAccess' => $hasAccess,
            'completed' => $isEnrolled && $lesson->isCompletedBy($user),
            'unlocked' => $hasAccess && $lesson->isUnlockedFor($user),
        ]];
    });
    $nextLesson = $isEnrolled
        ? $course->lessons->first(fn ($lesson) => ! $lessonState[$lesson->id]['completed'] && $lessonState[$lesson->id]['unlocked'])
        : null;
    // Exams placed along the way (lesson quizzes, module tests, the final exam).
    $checkpoints = $course->checkpoints()->map(fn ($checkpoint) => $checkpoint + [
        'status' => $isEnrolled ? $course->checkpointStatus($checkpoint, $user) : 'locked',
    ]);
    $lessonQuizzes = $checkpoints->where('kind', 'lesson')->keyBy(fn ($checkpoint) => $checkpoint['lesson']->id);
    $moduleTests = $checkpoints->where('kind', 'module')->keyBy(fn ($checkpoint) => $checkpoint['module']->id);
    $finalCheckpoint = $checkpoints->firstWhere('kind', 'final');
    // When no lesson is available, the learner is usually waiting on a quiz.
    $nextCheckpoint = $isEnrolled && ! $nextLesson
        ? $checkpoints->first(fn ($checkpoint) => $checkpoint['status'] === 'open' && $checkpoint['exam']->is_published)
        : null;
    $previewCount = $course->lessons->where('is_preview', true)->count();
    $attachmentCount = $course->lessons->sum(fn ($lesson) => $lesson->attachments->count());

    // Curriculum sections: each course module, then any lessons not in a module.
    $sections = $course->modules->map(fn ($module) => [
        'module_id' => $module->id,
        'title' => $module->title,
        'lessons' => $course->lessons->where('course_module_id', $module->id)->values(),
    ])->filter(fn ($section) => $section['lessons']->isNotEmpty())->values();
    $loose = $course->lessons->whereNull('course_module_id')->values();
    if ($loose->isNotEmpty()) {
        $sections->push(['module_id' => null, 'title' => $sections->isEmpty() ? 'Lessons' : 'More lessons', 'lessons' => $loose]);
    }
    $lessonNumbers = $course->lessons->pluck('id')->flip()->map(fn ($index) => $index + 1);
@endphp

<x-app-layout :title="$course->title" flush>
    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-ink-950 text-white">
        <div class="bg-dots absolute inset-0 -z-10"></div>
        <div class="absolute -top-40 right-0 -z-10 h-[28rem] w-[28rem] rounded-full bg-brand-600/25 blur-3xl"></div>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <div class="lg:max-w-[calc(100%-24rem)] lg:pr-10">
                <nav class="flex items-center gap-2 text-sm text-slate-400" aria-label="{{ __('Breadcrumb') }}">
                    <a href="{{ route('lms.courses.index') }}" class="hover:text-white">{{ __('Courses') }}</a>
                    <x-icon name="chevron-right" class="h-4 w-4" />
                    <span class="truncate text-slate-300">{{ $course->title }}</span>
                </nav>

                @if ($course->category)
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-brand-300">{{ $course->category }}</p>
                @endif
                <h1 class="{{ $course->category ? 'mt-1' : 'mt-4' }} font-display text-3xl leading-tight font-bold tracking-tight sm:text-4xl">{{ $course->title }}</h1>

                @if ($course->subtitle)
                    <p class="mt-4 text-lg leading-relaxed text-slate-300">{{ $course->subtitle }}</p>
                @elseif ($course->description)
                    <p class="mt-4 text-base leading-relaxed text-slate-300">{{ \Illuminate\Support\Str::limit(trim(strip_tags($course->description)), 220) }}</p>
                @endif

                @php($byline = $course->instructors->isNotEmpty() ? $course->instructors->pluck('name')->join(', ', ' and ') : $course->instructor_name)
                @if ($course->reviews_count > 0 || $byline)
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                        @if ($course->reviews_count > 0)
                            <a href="#reviews" class="inline-flex items-center gap-1.5 hover:underline">
                                <span class="font-bold text-amber-300">{{ number_format($course->reviews_avg_stars, 1) }}</span>
                                <x-stars :value="$course->reviews_avg_stars" />
                                <span class="text-slate-400">({{ number_format($course->reviews_count) }} {{ trans_choice('rating|ratings', $course->reviews_count) }})</span>
                            </a>
                        @endif
                        @if ($byline)
                            <span class="text-slate-300">{{ __('Taught by') }} <a href="#instructor" class="font-medium text-white underline decoration-white/30 hover:decoration-white">{{ $byline }}</a></span>
                        @endif
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-300">
                    <span class="inline-flex items-center gap-1.5"><x-icon name="play" class="h-4 w-4 text-brand-300" /> {{ $course->lessons->count() }} {{ trans_choice('lesson|lessons', $course->lessons->count()) }}</span>
                    @if ($course->modules->isNotEmpty())
                        <span class="inline-flex items-center gap-1.5"><x-icon name="stack" class="h-4 w-4 text-brand-300" /> {{ $course->modules->count() }} {{ trans_choice('module|modules', $course->modules->count()) }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1.5"><x-icon name="users" class="h-4 w-4 text-brand-300" /> {{ number_format($course->enrollments_count) }} {{ trans_choice('learner|learners', $course->enrollments_count) }}</span>
                    @if ($course->certificate_policy->value !== 'none')
                        <span class="inline-flex items-center gap-1.5"><x-icon name="trophy" class="h-4 w-4 text-amber-300" /> {{ __('Certificate of completion') }}</span>
                    @endif
                    @if ($course->durationLabel())
                        <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="h-4 w-4 text-brand-300" /> {{ $course->durationLabel() }}</span>
                    @endif
                    @if ($course->level)
                        <span class="inline-flex items-center gap-1.5"><x-icon name="chart-bar" class="h-4 w-4 text-brand-300" /> {{ $course->level->label() }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 text-slate-400"><x-icon name="calendar" class="h-4 w-4" /> {{ __('Updated :updated_at', ['updated_at' => $course->updated_at->translatedFormat('M Y')]) }}</span>
                </div>

                @if ($isEnrolled)
                    <div class="mt-6 max-w-md">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-medium text-white">{{ __('Your progress') }}</span>
                            <span class="text-slate-300">{{ __(':progress% complete', ['progress' => $progress]) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full {{ $progress === 100 ? 'bg-emerald-400' : 'bg-brand-400' }}" style="width: {{ $progress }}%"></div>
                        </div>
                        @if ($course->assessment_mode->value !== 'none')
                            <x-badge :color="$isPassed ? 'green' : 'amber'" class="mt-3">{{ $isPassed ? __('Passed') : __('Not yet passed') }}</x-badge>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col-reverse gap-10 py-10 lg:flex-row lg:items-start">
            {{-- Main column --}}
            <div class="min-w-0 flex-1 space-y-8">
                @if ($isEnrolled && $isPassed && $course->certificate_policy->value !== 'none')
                    <div class="flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 p-6 ring-1 ring-amber-200 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600"><x-icon name="trophy" class="h-6 w-6" /></span>
                            <div>
                                <p class="font-semibold text-slate-900">{{ __('Congratulations — you\'ve completed this course!') }}</p>
                                <p class="text-sm text-slate-600">{{ __('Your certificate of completion is ready.') }}</p>
                            </div>
                        </div>
                        @if ($certificate)
                            <x-button :href="route('lms.courses.certificate.download', $course)" variant="dark" icon="download">{{ __('Download certificate') }}</x-button>
                        @elseif ($course->certificateIsPaidHere())
                            <x-button :href="route('lms.courses.certificate-purchase.create', $course)">
                                {{ __('Get certificate for :certificate_price :certificate_currency', ['certificate_price' => number_format($course->certificate_price, 2), 'certificate_currency' => $course->certificate_currency]) }}
                            </x-button>
                        @endif
                    </div>
                @endif

                @if ($unmetPrerequisites->isNotEmpty())
                    <div class="rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200 sm:p-6">
                        <div class="flex items-start gap-3">
                            <x-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-amber-900">{{ $unmetPrerequisites->count() === 1 ? __('Complete this course first') : __('Complete these courses first') }}</p>
                                <p class="mt-0.5 text-sm text-amber-800">{{ $unmetPrerequisites->count() === 1 ? __('This course opens once you\'ve passed it.') : __('This course opens once you\'ve passed each of them.') }}</p>
                                <ul class="mt-4 space-y-2">
                                    @foreach ($unmetPrerequisites as $prerequisite)
                                        @php($started = $prerequisite->isEnrolled($user))
                                        <li>
                                            <a href="{{ route('lms.courses.show', $prerequisite) }}" class="flex items-center justify-between gap-4 rounded-xl bg-white px-4 py-3 ring-1 ring-amber-200 hover:ring-amber-300">
                                                <span class="min-w-0 truncate text-sm font-medium text-slate-900">{{ $prerequisite->title }}</span>
                                                <span class="shrink-0 text-xs font-semibold {{ $started ? 'text-brand-700' : 'text-slate-500' }}">
                                                    {{ $started ? $prerequisite->progressPercentFor($user).'% done — continue' : __('Start') }} &rarr;
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($isEnrolled && $course->assessment_mode->value === 'course_final' && $progress === 100 && ! $isPassed)
                    <div class="flex items-start gap-3 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200">
                        <x-icon name="flag" class="mt-0.5 h-5 w-5 text-amber-600" />
                        <p class="text-sm font-medium text-amber-900">
                            {!! __('All lessons complete — pass :exam to complete this course.', ['exam' => $course->finalExam?->is_published
                                ? '<a href="'.e(route('cbt.exams.show', $course->finalExam)).'" class="underline">'.e($course->finalExam->title).'</a>'
                                : e($course->finalExam->title ?? __('the final exam'))]) !!}
                        </p>
                    </div>
                @endif

                @if (! empty($course->outcomes))
                    <section class="rounded-2xl bg-white p-6 ring-1 ring-slate-200/80 sm:p-8">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('What you\'ll learn') }}</h2>
                        <ul class="mt-5 grid gap-x-8 gap-y-3 sm:grid-cols-2">
                            @foreach ($course->outcomes as $outcome)
                                <li class="flex gap-3 text-[15px] text-slate-700"><x-icon name="check" class="mt-0.5 h-5 w-5 text-emerald-600" /> {{ $outcome }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (mb_strlen(trim(strip_tags((string) $course->description))) > 220 || ($course->subtitle && $course->description))
                    <section class="rounded-2xl bg-white p-6 ring-1 ring-slate-200/80 sm:p-8">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('About this course') }}</h2>
                        <div class="rich-text mt-4 text-[15px] text-slate-700">{!! $course->description !!}</div>
                    </section>
                @endif

                <section>
                    <div class="mb-4 flex items-end justify-between">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Course content') }}</h2>
                        <p class="text-sm text-slate-500">{{ $sections->count() }} {{ trans_choice('section|sections', $sections->count()) }} &middot; {{ $course->lessons->count() }} {{ trans_choice('lesson|lessons', $course->lessons->count()) }}</p>
                    </div>

                    @if ($course->lessons->isEmpty())
                        <x-empty-state icon="play" :title="__('No lessons yet')" :description="__('Lessons will appear here once they\'re published.')" />
                    @else
                        <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80">
                            @foreach ($sections as $section)
                                @php($doneInSection = $section['lessons']->filter(fn ($lesson) => $lessonState[$lesson->id]['completed'])->count())
                                <details class="group border-b border-slate-200 last:border-b-0" @if ($loop->first || $sections->count() <= 3) open @endif>
                                    <summary class="flex cursor-pointer items-center justify-between gap-4 bg-slate-50 px-5 py-4 hover:bg-slate-100/70">
                                        <span class="flex items-center gap-3">
                                            <x-icon name="chevron-down" class="details-chevron h-4 w-4 text-slate-500 transition" />
                                            <span class="text-sm font-semibold text-slate-900">{{ $section['title'] }}</span>
                                        </span>
                                        <span class="shrink-0 text-xs text-slate-500">
                                            @if ($isEnrolled){{ $doneInSection }} / @endif{{ $section['lessons']->count() }} {{ trans_choice('lesson|lessons', $section['lessons']->count()) }}
                                        </span>
                                    </summary>
                                    <ul class="divide-y divide-slate-100">
                                        @foreach ($section['lessons'] as $lesson)
                                            @php($state = $lessonState[$lesson->id])
                                            @php($canOpen = $state['hasAccess'] && $state['unlocked'])
                                            <li>
                                                <a href="{{ $canOpen ? route('lms.lessons.show', [$course, $lesson]) : '#' }}"
                                                   class="flex items-center justify-between gap-4 px-5 py-3.5 {{ $canOpen ? 'hover:bg-slate-50' : 'cursor-not-allowed' }}">
                                                    <span class="flex min-w-0 items-center gap-3">
                                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $state['completed'] ? 'bg-emerald-100 text-emerald-700' : ($canOpen ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-500') }}">
                                                            @if ($state['completed'])
                                                                <x-icon name="check" class="h-4 w-4" />
                                                            @elseif ($state['hasAccess'] && ! $state['unlocked'])
                                                                <x-icon name="lock" class="h-3.5 w-3.5" />
                                                            @elseif (! $state['hasAccess'])
                                                                <x-icon name="lock" class="h-3.5 w-3.5" />
                                                            @else
                                                                {{ $lessonNumbers[$lesson->id] }}
                                                            @endif
                                                        </span>
                                                        <span class="truncate text-sm {{ $canOpen ? 'font-medium text-slate-900' : 'text-slate-500' }}">{{ $lesson->title }}</span>
                                                        @if ($lesson->is_preview && ! $isEnrolled)
                                                            <x-badge color="brand">{{ __('Preview') }}</x-badge>
                                                        @endif
                                                    </span>
                                                    <span class="flex shrink-0 items-center gap-3">
                                                        @if ($state['hasAccess'] && ! $state['unlocked'])
                                                            <span class="hidden text-xs text-slate-500 sm:inline">{{ $unmetPrerequisites->isNotEmpty() ? __('Complete the required course first') : __('Finish the step above to unlock') }}</span>
                                                        @endif
                                                        @if ($lesson->video_url)
                                                            <x-icon name="play" class="h-4 w-4 text-slate-500" :title="__('Video lesson')" />
                                                        @endif
                                                        @if ($lesson->attachments->isNotEmpty())
                                                            <x-icon name="document" class="h-4 w-4 text-slate-500" :title="__('Has attachments')" />
                                                        @endif
                                                    </span>
                                                </a>
                                            </li>
                                            @if ($lessonQuizzes->has($lesson->id))
                                                @include('lms::courses._checkpoint', ['checkpoint' => $lessonQuizzes[$lesson->id]])
                                            @endif
                                        @endforeach
                                        @if ($section['module_id'] && $moduleTests->has($section['module_id']))
                                            @include('lms::courses._checkpoint', ['checkpoint' => $moduleTests[$section['module_id']]])
                                        @endif
                                    </ul>
                                </details>
                            @endforeach
                            @if ($finalCheckpoint)
                                <ul class="border-t border-slate-200">
                                    @include('lms::courses._checkpoint', ['checkpoint' => $finalCheckpoint])
                                </ul>
                            @endif
                        </div>
                    @endif
                </section>

                @if ($course->instructors->isNotEmpty())
                    <section id="instructor" class="scroll-mt-24 rounded-2xl bg-white p-6 ring-1 ring-slate-200/80 sm:p-8">
                        <h2 class="text-xl font-bold text-slate-900">{{ $course->instructors->count() > 1 ? __('Your instructors') : __('Your instructor') }}</h2>
                        <div class="mt-5 space-y-5">
                            @foreach ($course->instructors as $instructor)
                                <div class="flex gap-4">
                                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display text-lg font-bold text-brand-700">
                                        {{ collect(explode(' ', $instructor->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                                    </span>
                                    <div>
                                        <p class="text-base font-semibold text-slate-900">{{ $instructor->name }}</p>
                                        @if ($instructor->bio)
                                            <p class="mt-1 text-sm leading-relaxed whitespace-pre-line text-slate-600">{{ $instructor->bio }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @elseif ($course->instructor_name)
                    <section id="instructor" class="scroll-mt-24 rounded-2xl bg-white p-6 ring-1 ring-slate-200/80 sm:p-8">
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Your instructor') }}</h2>
                        <div class="mt-5 flex gap-4">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display text-lg font-bold text-brand-700">
                                {{ collect(explode(' ', $course->instructor_name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                            </span>
                            <div>
                                <p class="text-base font-semibold text-slate-900">{{ $course->instructor_name }}</p>
                                @if ($course->instructor_bio)
                                    <p class="mt-1 text-sm leading-relaxed whitespace-pre-line text-slate-600">{{ $course->instructor_bio }}</p>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                @include('lms::courses._reviews')
            </div>

            {{-- Enrollment card: overlaps the hero on large screens --}}
            <aside class="w-full lg:sticky lg:top-32 lg:-mt-56 lg:w-96 lg:shrink-0">
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-900/10 ring-1 ring-slate-200">
                    @if ($course->cover_image_path)
                        <img src="{{ $course->coverUrl() }}" alt="" class="aspect-video w-full object-cover">
                    @else
                        <x-cover :seed="$course->title" icon="academic-cap" class="aspect-video" />
                    @endif
                    <div class="p-6">
                        @if ($isEnrolled)
                            <p class="text-sm font-medium text-slate-500">{{ __('You\'re enrolled') }}</p>
                            <x-progress class="mt-2" :value="$progress" label />
                            @if ($unmetPrerequisites->isNotEmpty())
                                <x-button :href="route('lms.courses.show', $unmetPrerequisites->first())" class="mt-5 w-full" size="lg" variant="secondary" icon="lock">
                                    {{ __('Complete :title first', ['title' => \Illuminate\Support\Str::limit($unmetPrerequisites->first()->title, 28)]) }}
                                </x-button>
                            @elseif ($nextLesson)
                                <x-button :href="route('lms.lessons.show', [$course, $nextLesson])" class="mt-5 w-full" size="lg" icon-right="arrow-right">
                                    {{ $progress === 0 ? __('Start learning') : __('Continue learning') }}
                                </x-button>
                                <p class="mt-2 truncate text-center text-xs text-slate-500">{{ __('Next: :title', ['title' => $nextLesson->title]) }}</p>
                            @elseif ($nextCheckpoint)
                                <x-button :href="route('cbt.exams.show', $nextCheckpoint['exam'])" class="mt-5 w-full" size="lg" icon-right="arrow-right">
                                    {{ __('Take the :next_checkpoint', ['next_checkpoint' => strtolower($nextCheckpoint['label'])]) }}
                                </x-button>
                                <p class="mt-2 truncate text-center text-xs text-slate-500">{{ $nextCheckpoint['kind'] === 'final' ? __('Pass it to complete the course') : __('Pass it to continue') }}: {{ $nextCheckpoint['exam']->title }}</p>
                            @elseif ($progress === 100)
                                <p class="mt-5 flex items-center justify-center gap-2 rounded-lg bg-emerald-50 py-3 text-sm font-semibold text-emerald-700"><x-icon name="check-circle" /> {{ __('All lessons complete') }}</p>
                            @endif
                        @elseif ($unmetPrerequisites->isNotEmpty())
                            <p class="text-3xl font-bold {{ $course->isPaidHere() ? 'text-slate-900' : 'text-emerald-600' }}">
                                {{ $course->isPaidHere() ? $course->currency.' '.number_format($course->price, 2) : __('Free') }}
                            </p>
                            <p class="mt-4 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-sm text-amber-900 ring-1 ring-amber-200">
                                <x-icon name="lock" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                                <span>{{ __('Opens after you pass :join.', ['join' => $unmetPrerequisites->pluck('title')->map(fn ($title) => '“'.$title.'”')->join(', ', ' '.__('and').' ')]) }}</span>
                            </p>
                            <x-button :href="route('lms.courses.show', $unmetPrerequisites->first())" class="mt-4 w-full" size="lg" icon-right="arrow-right">{{ __('Go to :title', ['title' => \Illuminate\Support\Str::limit($unmetPrerequisites->first()->title, 30)]) }}</x-button>
                        @else
                            @if ($course->isPaidHere())
                                <p class="text-3xl font-bold text-slate-900">{{ $course->currency }} {{ number_format($course->price, 2) }}</p>
                                <x-button :href="route('lms.courses.purchase.create', $course)" class="mt-5 w-full" size="lg">{{ __('Buy for :price :currency', ['price' => number_format($course->price, 2), 'currency' => $course->currency]) }}</x-button>
                            @else
                                <p class="text-3xl font-bold text-emerald-600">{{ __('Free') }}</p>
                                <form method="POST" action="{{ route('lms.courses.enroll', $course) }}" class="mt-5">
                                    @csrf
                                    <x-button type="submit" class="w-full" size="lg">{{ __('Enroll') }}</x-button>
                                </form>
                            @endif
                            @if ($previewCount > 0)
                                <p class="mt-3 text-center text-xs text-slate-500">{{ __(':preview_count free preview :noun available', ['preview_count' => $previewCount, 'noun' => trans_choice('lesson|lessons', $previewCount)]) }}</p>
                            @endif
                        @endif

                        @if ($isEnrolled || \Elibrary\Lms\Support\Teaching::canManage($user, $course))
                            <a href="{{ route('lms.discussions.index', $course) }}" class="mt-4 flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50">
                                <x-icon name="chat" class="h-4 w-4" /> {{ __('Discussions') }}
                                @if ($discussionCount)<span class="rounded-full bg-brand-100 px-2 text-xs">{{ $discussionCount }}</span>@endif
                            </a>
                        @endif

                        <div class="mt-6 border-t border-slate-100 pt-5">
                            <p class="text-sm font-semibold text-slate-900">{{ __('This course includes') }}</p>
                            <ul class="mt-3 space-y-2.5 text-sm text-slate-600">
                                <li class="flex items-center gap-3"><x-icon name="play" class="h-4 w-4 text-slate-500" /> {{ $course->lessons->count() }} {{ trans_choice('lesson|lessons', $course->lessons->count()) }}</li>
                                @if ($attachmentCount > 0)
                                    <li class="flex items-center gap-3"><x-icon name="document" class="h-4 w-4 text-slate-500" /> {{ __(':attachment_count downloadable :noun', ['attachment_count' => $attachmentCount, 'noun' => trans_choice('resource|resources', $attachmentCount)]) }}</li>
                                @endif
                                @if ($course->isShared())
                                    <li class="flex items-center gap-3"><x-icon name="globe" class="h-4 w-4 text-slate-500" /> {{ __('Shared by :name', ['name' => $course->tenant?->name ?? __('another workspace')]) }}</li>
                                @endif
                                @if ($course->sequential_lessons)
                                    <li class="flex items-center gap-3"><x-icon name="flag" class="h-4 w-4 text-slate-500" /> {{ __('Lessons unlock in order') }}</li>
                                @endif
                                @foreach ($course->paths->where('is_published', true) as $path)
                                    <li class="flex items-start gap-3"><x-icon name="map" class="mt-0.5 h-4 w-4 text-slate-500" /> <span>{!! __('Part of the :path path', ['path' => '<a href="'.e(route('lms.paths.show', $path)).'" class="font-medium text-brand-700 hover:underline">'.e($path->title).'</a>']) !!}</span></li>
                                @endforeach
                                @if ($course->prerequisites->isNotEmpty())
                                    <li class="flex items-start gap-3"><x-icon name="lock" class="mt-0.5 h-4 w-4 text-slate-500" /> <span>{{ __('Requires :join', ['join' => $course->prerequisites->pluck('title')->join(', ', ' '.__('and').' ')]) }}</span></li>
                                @endif
                                @if ($course->assessment_mode->value !== 'none')
                                    <li class="flex items-center gap-3"><x-icon name="clipboard-check" class="h-4 w-4 text-slate-500" /> {{ $course->assessment_mode->value === 'course_final' ? __('Final exam') : __('Assessments along the way') }}</li>
                                @endif
                                @if ($course->certificate_policy->value !== 'none')
                                    <li class="flex items-center gap-3"><x-icon name="trophy" class="h-4 w-4 text-slate-500" /> {{ ($course->certificateIsPaidHere() ? __('Certificate of completion (paid)') : __('Certificate of completion')) }}</li>
                                @endif
                                <li class="flex items-center gap-3"><x-icon name="device" class="h-4 w-4 text-slate-500" /> {{ __('Learn on any device, at your own pace') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
