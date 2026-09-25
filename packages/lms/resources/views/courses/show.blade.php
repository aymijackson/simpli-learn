<x-app-layout :title="$course->title">
    <a href="{{ route('lms.courses.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to courses
    </a>

    <x-page-header :title="$course->title">
        <x-slot:actions>
            @if (! $isEnrolled)
                @if ($course->pricing_policy->value === 'paid')
                    <x-button :href="route('lms.courses.purchase.create', $course)">Buy for {{ number_format($course->price, 2) }} {{ $course->currency }}</x-button>
                @else
                    <form method="POST" action="{{ route('lms.courses.enroll', $course) }}">
                        @csrf
                        <x-button type="submit">Enroll</x-button>
                    </form>
                @endif
            @else
                <div class="flex items-center gap-2">
                    <x-badge color="indigo">{{ $progress }}% complete</x-badge>
                    @if ($course->assessment_mode->value !== 'none')
                        <x-badge :color="$isPassed ? 'green' : 'amber'">{{ $isPassed ? 'Passed' : 'Not yet passed' }}</x-badge>
                    @endif
                </div>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($course->description)
        <div class="rich-text mb-8 text-sm text-slate-600">{!! $course->description !!}</div>
    @endif

    @if ($isEnrolled && $isPassed && $course->certificate_policy->value !== 'none')
        <x-card class="mb-6 text-center">
            @if ($certificate)
                <x-button :href="route('lms.courses.certificate.download', $course)" variant="secondary">Download certificate</x-button>
            @elseif ($course->certificate_policy->value === 'paid')
                <x-button :href="route('lms.courses.certificate-purchase.create', $course)">
                    Get certificate for {{ number_format($course->certificate_price, 2) }} {{ $course->certificate_currency }}
                </x-button>
            @endif
        </x-card>
    @endif

    @if ($isEnrolled && $course->assessment_mode->value === 'course_final' && $progress === 100 && ! $isPassed)
        <x-card class="mb-6 border border-amber-200 bg-amber-50">
            <p class="text-sm font-medium text-amber-800">
                All lessons complete — pass
                @if ($course->finalExam?->is_published)
                    <a href="{{ route('cbt.exams.show', $course->finalExam) }}" class="underline">{{ $course->finalExam->title }}</a>
                @else
                    {{ $course->finalExam->title ?? 'the final exam' }}
                @endif
                to complete this course.
            </p>
        </x-card>
    @endif

    @if ($course->lessons->isEmpty())
        <x-empty-state title="No lessons yet" />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($course->lessons as $lesson)
                    @php($hasAccess = $isEnrolled || $lesson->is_preview)
                    @php($completed = $isEnrolled && $lesson->isCompletedBy(auth()->user()))
                    @php($unlocked = $hasAccess && $lesson->isUnlockedFor(auth()->user()))
                    <li>
                        <a href="{{ $hasAccess && $unlocked ? route('lms.lessons.show', [$course, $lesson]) : '#' }}"
                           class="flex items-center justify-between gap-4 px-6 py-4 {{ $hasAccess && $unlocked ? 'hover:bg-slate-50' : 'cursor-not-allowed opacity-60' }}">
                            <div class="flex items-center gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $completed ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    @if ($completed)
                                        &check;
                                    @elseif ($hasAccess && ! $unlocked)
                                        &#128274;
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>
                                <span class="text-sm font-medium text-slate-900">{{ $lesson->title }}</span>
                                @if ($lesson->is_preview && ! $isEnrolled)
                                    <x-badge color="indigo">Preview</x-badge>
                                @endif
                            </div>
                            @if ($hasAccess && ! $unlocked)
                                @php($requiredExam = $lesson->unlockRequirement(auth()->user()))
                                <span class="text-xs text-slate-500">
                                    @if ($requiredExam)
                                        Pass "{{ $requiredExam->title }}" to unlock
                                    @else
                                        Complete the previous lesson/module to unlock
                                    @endif
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
