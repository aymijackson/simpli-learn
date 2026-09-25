@php
    $user = auth()->user();
    $lessons = $course->lessons;
    $position = $lessons->search(fn ($item) => $item->id === $lesson->id) + 1;
    $completedIds = $lessons->filter(fn ($item) => $item->isCompletedBy($user))->pluck('id');
    $percent = $lessons->count() ? (int) round($completedIds->count() / $lessons->count() * 100) : 0;
@endphp

<x-app-layout :title="$lesson->title" flush>
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="min-w-0">
                <a href="{{ route('lms.courses.show', $course) }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
                    <x-icon name="arrow-left" class="h-4 w-4" /> {{ $course->title }}
                </a>
            </div>
            <div class="flex items-center gap-3 sm:w-72">
                <x-progress class="flex-1" :value="$percent" label />
            </div>
        </div>
    </div>

    <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:flex-row lg:px-8">
        {{-- Lesson --}}
        <article class="min-w-0 flex-1">
            <div class="rounded-2xl bg-white ring-1 ring-slate-200/80">
                <div class="border-b border-slate-100 px-6 py-6 sm:px-10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Lesson {{ $position }} of {{ $lessons->count() }}</p>
                    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
                        <h1 class="font-display text-2xl leading-tight font-bold text-slate-900 sm:text-3xl">{{ $lesson->title }}</h1>
                        @if ($isCompleted)
                            <x-badge color="green" icon="check">Completed</x-badge>
                        @endif
                    </div>
                </div>

                <div class="rich-text px-6 py-8 text-[15px] leading-relaxed text-slate-700 sm:px-10">{!! $lesson->content !!}</div>

                @if ($lesson->attachments->isNotEmpty())
                    <div class="space-y-3 border-t border-slate-100 px-6 py-8 sm:px-10">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="document" class="h-4 w-4 text-slate-400" /> Attachments</h2>
                        @foreach ($lesson->attachments as $attachment)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="mb-3 text-sm font-medium text-slate-900">{{ $attachment->title }}</p>
                                @if ($attachment->access_level->value === 'open')
                                    <x-button :href="route('lms.lessons.attachments.download', [$course, $lesson, $attachment])" variant="secondary" size="sm" icon="download">Download</x-button>
                                @elseif ($attachment->type->value === 'video')
                                    <video controls preload="none" class="w-full rounded-lg bg-black" src="{{ route('lms.lessons.attachments.stream', [$course, $lesson, $attachment]) }}"></video>
                                @elseif ($attachment->type->value === 'audio')
                                    <audio controls preload="none" class="w-full" src="{{ route('lms.lessons.attachments.stream', [$course, $lesson, $attachment]) }}"></audio>
                                @else
                                    <x-button :href="route('lms.lessons.attachments.stream', [$course, $lesson, $attachment])" target="_blank" variant="secondary" size="sm" icon="eye">View</x-button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-10">
                    <div>
                        @if ($previous)
                            <x-button variant="ghost" :href="route('lms.lessons.show', [$course, $previous])" icon="arrow-left">Previous</x-button>
                        @endif
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        @unless ($isCompleted)
                            <form method="POST" action="{{ route('lms.lessons.complete', [$course, $lesson]) }}">
                                @csrf
                                <x-button type="submit" icon="check" class="w-full sm:w-auto">Mark as complete</x-button>
                            </form>
                        @endunless
                        @if ($next)
                            <x-button :variant="$isCompleted ? 'primary' : 'secondary'" :href="route('lms.lessons.show', [$course, $next])" icon-right="arrow-right">Next lesson</x-button>
                        @endif
                    </div>
                </div>
            </div>
        </article>

        {{-- Course outline --}}
        <aside class="lg:w-80 lg:shrink-0">
            <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80 lg:sticky lg:top-32">
                <div class="border-b border-slate-100 px-5 py-4">
                    <p class="text-sm font-semibold text-slate-900">Course content</p>
                    <p class="text-xs text-slate-500">{{ $completedIds->count() }} of {{ $lessons->count() }} complete</p>
                </div>
                <ol class="max-h-[60vh] divide-y divide-slate-100 overflow-y-auto">
                    @foreach ($lessons as $item)
                        @php($current = $item->id === $lesson->id)
                        @php($done = $completedIds->contains($item->id))
                        @php($open = $current || ($item->isAccessibleTo($user) && $item->isUnlockedFor($user)))
                        <li>
                            <a @if ($open) href="{{ route('lms.lessons.show', [$course, $item]) }}" @else aria-disabled="true" @endif
                               class="flex items-center gap-3 px-5 py-3 text-sm {{ $current ? 'bg-brand-50 font-semibold text-brand-800' : ($open ? 'text-slate-700 hover:bg-slate-50' : 'cursor-not-allowed text-slate-400') }}">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold {{ $done ? 'bg-emerald-100 text-emerald-700' : ($current ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500') }}">
                                    @if ($done)<x-icon name="check" class="h-3.5 w-3.5" />@elseif (! $open)<x-icon name="lock" class="h-3 w-3" />@else{{ $loop->iteration }}@endif
                                </span>
                                <span class="line-clamp-2">{{ $item->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </aside>
    </div>
</x-app-layout>
