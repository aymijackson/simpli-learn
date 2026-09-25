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
    $previewCount = $course->lessons->where('is_preview', true)->count();
    $attachmentCount = $course->lessons->sum(fn ($lesson) => $lesson->attachments->count());

    // Curriculum sections: each course module, then any lessons not in a module.
    $sections = $course->modules->map(fn ($module) => [
        'title' => $module->title,
        'lessons' => $course->lessons->where('course_module_id', $module->id)->values(),
    ])->filter(fn ($section) => $section['lessons']->isNotEmpty())->values();
    $loose = $course->lessons->whereNull('course_module_id')->values();
    if ($loose->isNotEmpty()) {
        $sections->push(['title' => $sections->isEmpty() ? 'Lessons' : 'More lessons', 'lessons' => $loose]);
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
                <nav class="flex items-center gap-2 text-sm text-slate-400">
                    <a href="{{ route('lms.courses.index') }}" class="hover:text-white">Courses</a>
                    <x-icon name="chevron-right" class="h-4 w-4" />
                    <span class="truncate text-slate-300">{{ $course->title }}</span>
                </nav>

                <h1 class="mt-4 font-display text-3xl leading-tight font-bold tracking-tight sm:text-4xl">{{ $course->title }}</h1>

                @if ($course->description)
                    <p class="mt-4 text-base leading-relaxed text-slate-300">{{ \Illuminate\Support\Str::limit(trim(strip_tags($course->description)), 220) }}</p>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-300">
                    <span class="inline-flex items-center gap-1.5"><x-icon name="play" class="h-4 w-4 text-brand-300" /> {{ $course->lessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $course->lessons->count()) }}</span>
                    @if ($course->modules->isNotEmpty())
                        <span class="inline-flex items-center gap-1.5"><x-icon name="stack" class="h-4 w-4 text-brand-300" /> {{ $course->modules->count() }} {{ \Illuminate\Support\Str::plural('module', $course->modules->count()) }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1.5"><x-icon name="users" class="h-4 w-4 text-brand-300" /> {{ number_format($course->enrollments_count) }} {{ \Illuminate\Support\Str::plural('learner', $course->enrollments_count) }}</span>
                    @if ($course->certificate_policy->value !== 'none')
                        <span class="inline-flex items-center gap-1.5"><x-icon name="trophy" class="h-4 w-4 text-amber-300" /> Certificate of completion</span>
                    @endif
                </div>

                @if ($isEnrolled)
                    <div class="mt-6 max-w-md">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-medium text-white">Your progress</span>
                            <span class="text-slate-300">{{ $progress }}% complete</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full {{ $progress === 100 ? 'bg-emerald-400' : 'bg-brand-400' }}" style="width: {{ $progress }}%"></div>
                        </div>
                        @if ($course->assessment_mode->value !== 'none')
                            <x-badge :color="$isPassed ? 'green' : 'amber'" class="mt-3">{{ $isPassed ? 'Passed' : 'Not yet passed' }}</x-badge>
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
                                <p class="font-semibold text-slate-900">Congratulations — you've completed this course!</p>
                                <p class="text-sm text-slate-600">Your certificate of completion is ready.</p>
                            </div>
                        </div>
                        @if ($certificate)
                            <x-button :href="route('lms.courses.certificate.download', $course)" variant="dark" icon="download">Download certificate</x-button>
                        @elseif ($course->certificate_policy->value === 'paid')
                            <x-button :href="route('lms.courses.certificate-purchase.create', $course)">
                                Get certificate for {{ number_format($course->certificate_price, 2) }} {{ $course->certificate_currency }}
                            </x-button>
                        @endif
                    </div>
                @endif

                @if ($isEnrolled && $course->assessment_mode->value === 'course_final' && $progress === 100 && ! $isPassed)
                    <div class="flex items-start gap-3 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200">
                        <x-icon name="flag" class="mt-0.5 h-5 w-5 text-amber-600" />
                        <p class="text-sm font-medium text-amber-900">
                            All lessons complete — pass
                            @if ($course->finalExam?->is_published)
                                <a href="{{ route('cbt.exams.show', $course->finalExam) }}" class="underline">{{ $course->finalExam->title }}</a>
                            @else
                                {{ $course->finalExam->title ?? 'the final exam' }}
                            @endif
                            to complete this course.
                        </p>
                    </div>
                @endif

                @if (mb_strlen(trim(strip_tags((string) $course->description))) > 220)
                    <section class="rounded-2xl bg-white p-6 ring-1 ring-slate-200/80 sm:p-8">
                        <h2 class="text-xl font-bold text-slate-900">About this course</h2>
                        <div class="rich-text mt-4 text-[15px] text-slate-700">{!! $course->description !!}</div>
                    </section>
                @endif

                <section>
                    <div class="mb-4 flex items-end justify-between">
                        <h2 class="text-xl font-bold text-slate-900">Course content</h2>
                        <p class="text-sm text-slate-500">{{ $sections->count() }} {{ \Illuminate\Support\Str::plural('section', $sections->count()) }} &middot; {{ $course->lessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $course->lessons->count()) }}</p>
                    </div>

                    @if ($course->lessons->isEmpty())
                        <x-empty-state icon="play" title="No lessons yet" description="Lessons will appear here once they're published." />
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
                                            @if ($isEnrolled){{ $doneInSection }} / @endif{{ $section['lessons']->count() }} {{ \Illuminate\Support\Str::plural('lesson', $section['lessons']->count()) }}
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
                                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $state['completed'] ? 'bg-emerald-100 text-emerald-700' : ($canOpen ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-400') }}">
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
                                                            <x-badge color="brand">Preview</x-badge>
                                                        @endif
                                                    </span>
                                                    <span class="flex shrink-0 items-center gap-3">
                                                        @if ($state['hasAccess'] && ! $state['unlocked'])
                                                            @php($requiredExam = $lesson->unlockRequirement($user))
                                                            <span class="hidden text-xs text-slate-500 sm:inline">
                                                                @if ($requiredExam)
                                                                    Pass "{{ $requiredExam->title }}" to unlock
                                                                @else
                                                                    Complete the previous lesson/module to unlock
                                                                @endif
                                                            </span>
                                                        @endif
                                                        @if ($lesson->attachments->isNotEmpty())
                                                            <x-icon name="document" class="h-4 w-4 text-slate-400" title="Has attachments" />
                                                        @endif
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            {{-- Enrollment card: overlaps the hero on large screens --}}
            <aside class="w-full lg:sticky lg:top-32 lg:-mt-56 lg:w-96 lg:shrink-0">
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-900/10 ring-1 ring-slate-200">
                    <x-cover :seed="$course->title" icon="academic-cap" class="aspect-video" />
                    <div class="p-6">
                        @if ($isEnrolled)
                            <p class="text-sm font-medium text-slate-500">You're enrolled</p>
                            <x-progress class="mt-2" :value="$progress" label />
                            @if ($nextLesson)
                                <x-button :href="route('lms.lessons.show', [$course, $nextLesson])" class="mt-5 w-full" size="lg" icon-right="arrow-right">
                                    {{ $progress === 0 ? 'Start learning' : 'Continue learning' }}
                                </x-button>
                                <p class="mt-2 truncate text-center text-xs text-slate-500">Next: {{ $nextLesson->title }}</p>
                            @elseif ($progress === 100)
                                <p class="mt-5 flex items-center justify-center gap-2 rounded-lg bg-emerald-50 py-3 text-sm font-semibold text-emerald-700"><x-icon name="check-circle" /> All lessons complete</p>
                            @endif
                        @else
                            @if ($course->pricing_policy->value === 'paid')
                                <p class="text-3xl font-bold text-slate-900">{{ $course->currency }} {{ number_format($course->price, 2) }}</p>
                                <x-button :href="route('lms.courses.purchase.create', $course)" class="mt-5 w-full" size="lg">Buy for {{ number_format($course->price, 2) }} {{ $course->currency }}</x-button>
                            @else
                                <p class="text-3xl font-bold text-emerald-600">Free</p>
                                <form method="POST" action="{{ route('lms.courses.enroll', $course) }}" class="mt-5">
                                    @csrf
                                    <x-button type="submit" class="w-full" size="lg">Enroll</x-button>
                                </form>
                            @endif
                            @if ($previewCount > 0)
                                <p class="mt-3 text-center text-xs text-slate-500">{{ $previewCount }} free preview {{ \Illuminate\Support\Str::plural('lesson', $previewCount) }} available</p>
                            @endif
                        @endif

                        <div class="mt-6 border-t border-slate-100 pt-5">
                            <p class="text-sm font-semibold text-slate-900">This course includes</p>
                            <ul class="mt-3 space-y-2.5 text-sm text-slate-600">
                                <li class="flex items-center gap-3"><x-icon name="play" class="h-4 w-4 text-slate-400" /> {{ $course->lessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $course->lessons->count()) }}</li>
                                @if ($attachmentCount > 0)
                                    <li class="flex items-center gap-3"><x-icon name="document" class="h-4 w-4 text-slate-400" /> {{ $attachmentCount }} downloadable {{ \Illuminate\Support\Str::plural('resource', $attachmentCount) }}</li>
                                @endif
                                @if ($course->assessment_mode->value !== 'none')
                                    <li class="flex items-center gap-3"><x-icon name="clipboard-check" class="h-4 w-4 text-slate-400" /> {{ $course->assessment_mode->value === 'course_final' ? 'Final exam' : 'Assessments along the way' }}</li>
                                @endif
                                @if ($course->certificate_policy->value !== 'none')
                                    <li class="flex items-center gap-3"><x-icon name="trophy" class="h-4 w-4 text-slate-400" /> Certificate of completion{{ $course->certificate_policy->value === 'paid' ? ' (paid)' : '' }}</li>
                                @endif
                                <li class="flex items-center gap-3"><x-icon name="device" class="h-4 w-4 text-slate-400" /> Learn on any device, at your own pace</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
