@php
    $user = auth()->user();
    $canMarkAnswer = $isStaff || $discussion->user_id === $user->id;
    $initials = fn ($name) => collect(explode(' ', (string) $name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
@endphp

<x-app-layout :title="$discussion->title">
    <a href="{{ route('lms.discussions.index', $course) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> Discussions &middot; {{ $course->title }}
    </a>

    <div class="mx-auto max-w-3xl space-y-4">
        <x-card>
            <div class="flex flex-wrap items-center gap-2">
                @if ($discussion->is_pinned)<x-badge color="brand">Pinned</x-badge>@endif
                @if ($discussion->is_locked)<x-badge color="slate">Closed</x-badge>@endif
                @if ($discussion->isResolved())<x-badge color="green" icon="check">Answered</x-badge>@endif
                @if ($discussion->lesson)
                    <a href="{{ route('lms.lessons.show', [$course, $discussion->lesson]) }}" class="text-xs font-medium text-brand-700 hover:underline">Lesson: {{ $discussion->lesson->title }}</a>
                @endif
            </div>
            <h1 class="mt-2 text-xl font-bold text-slate-900">{{ $discussion->title }}</h1>
            <p class="mt-1 text-xs text-slate-500">Asked by {{ $discussion->author?->name ?? 'Former member' }} &middot; {{ $discussion->created_at->diffForHumans() }}</p>
            <p class="mt-4 text-[15px] leading-relaxed whitespace-pre-line text-slate-700">{{ $discussion->body }}</p>

            @if ($isStaff || ($discussion->user_id === $user->id && $discussion->replies_count === 0))
                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                    @if ($isStaff)
                        @foreach ([$discussion->is_pinned ? 'unpin' : 'pin', $discussion->is_locked ? 'unlock' : 'lock'] as $action)
                            <form method="POST" action="{{ route('lms.discussions.moderate', [$course, $discussion]) }}">
                                @csrf
                                <input type="hidden" name="action" value="{{ $action }}">
                                <x-button type="submit" variant="secondary" size="sm">{{ ['pin' => 'Pin', 'unpin' => 'Unpin', 'lock' => 'Close replies', 'unlock' => 'Reopen'][$action] }}</x-button>
                            </form>
                        @endforeach
                    @endif
                    <form method="POST" action="{{ route('lms.discussions.destroy', [$course, $discussion]) }}" onsubmit="return confirm('Delete this discussion and all its replies?')">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="ghost" size="sm" class="text-red-700">Delete</x-button>
                    </form>
                </div>
            @endif
        </x-card>

        <h2 class="pt-2 text-sm font-semibold text-slate-900">{{ $discussion->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $discussion->replies_count) }}</h2>

        @foreach ($discussion->replies as $reply)
            @php($isAnswer = $discussion->answer_reply_id === $reply->id)
            <x-card :id="$loop->last ? 'latest' : 'reply-'.$reply->id" class="{{ $isAnswer ? 'ring-2 ring-emerald-400' : '' }}">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $reply->is_staff ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $initials($reply->author?->name) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-semibold text-slate-900">{{ $reply->author?->name ?? 'Former member' }}</span>
                            @if ($reply->is_staff)
                                <x-badge color="brand">{{ $reply->author?->isOwner() ? 'Owner' : 'Instructor' }}</x-badge>
                            @endif
                            @if ($isAnswer)<x-badge color="green" icon="check">Answer</x-badge>@endif
                            <span class="text-xs text-slate-500">{{ $reply->created_at->diffForHumans() }}</span>
                        </p>
                        <p class="mt-2 text-[15px] leading-relaxed whitespace-pre-line text-slate-700">{{ $reply->body }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                            @if ($canMarkAnswer)
                                <form method="POST" action="{{ route('lms.discussions.answer', [$course, $discussion, $reply]) }}">
                                    @csrf
                                    <button type="submit" class="font-semibold text-emerald-700 hover:underline">{{ $isAnswer ? 'Unmark answer' : 'Mark as answer' }}</button>
                                </form>
                            @endif
                            @if ($isStaff || $reply->user_id === $user->id)
                                <form method="POST" action="{{ route('lms.discussions.replies.destroy', [$course, $discussion, $reply]) }}" onsubmit="return confirm('Delete this reply?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-700 hover:underline">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>
        @endforeach

        @if ($discussion->is_locked && ! $isStaff)
            <p class="rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-600">This discussion is closed to new replies.</p>
        @else
            <x-card>
                <form method="POST" action="{{ route('lms.discussions.reply', [$course, $discussion]) }}" class="space-y-3">
                    @csrf
                    <x-textarea name="body" label="Your reply" rows="4" required maxlength="5000">{{ old('body') }}</x-textarea>
                    <x-button type="submit" icon="chat">Post reply</x-button>
                </form>
            </x-card>
        @endif
    </div>
</x-app-layout>
