<x-app-layout :title="$lesson->title">
    <a href="{{ route('lms.courses.show', $course) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $course->title }}
    </a>

    <x-card>
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-xl font-bold text-slate-900">{{ $lesson->title }}</h1>
            @if ($isCompleted)
                <x-badge color="green">Completed</x-badge>
            @endif
        </div>

        <div class="rich-text mt-6 text-sm text-slate-700">{!! $lesson->content !!}</div>

        @if ($lesson->attachments->isNotEmpty())
            <div class="mt-8 space-y-3 border-t border-slate-100 pt-6">
                <h2 class="text-sm font-semibold text-slate-900">Attachments</h2>
                @foreach ($lesson->attachments as $attachment)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="mb-2 text-sm font-medium text-slate-900">{{ $attachment->title }}</p>
                        @if ($attachment->access_level->value === 'open')
                            <a href="{{ route('lms.lessons.attachments.download', [$course, $lesson, $attachment]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Download &rarr;</a>
                        @elseif ($attachment->type->value === 'video')
                            <video controls preload="none" class="w-full max-w-xl rounded-lg" src="{{ route('lms.lessons.attachments.stream', [$course, $lesson, $attachment]) }}"></video>
                        @elseif ($attachment->type->value === 'audio')
                            <audio controls preload="none" class="w-full max-w-xl" src="{{ route('lms.lessons.attachments.stream', [$course, $lesson, $attachment]) }}"></audio>
                        @else
                            <a href="{{ route('lms.lessons.attachments.stream', [$course, $lesson, $attachment]) }}" target="_blank" class="text-sm font-medium text-brand-600 hover:text-brand-500">View &rarr;</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-8 flex items-center justify-between border-t border-slate-100 pt-6">
            <div>
                @if ($previous)
                    <x-button variant="secondary" :href="route('lms.lessons.show', [$course, $previous])">&larr; Previous</x-button>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @unless ($isCompleted)
                    <form method="POST" action="{{ route('lms.lessons.complete', [$course, $lesson]) }}">
                        @csrf
                        <x-button type="submit">Mark as complete</x-button>
                    </form>
                @endunless
                @if ($next)
                    <x-button variant="secondary" :href="route('lms.lessons.show', [$course, $next])">Next &rarr;</x-button>
                @endif
            </div>
        </div>
    </x-card>
</x-app-layout>
