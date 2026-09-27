<x-app-layout :title="__(':course — discussions', ['course' => $course->title])">
    <a href="{{ route('lms.courses.show', $course) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> {{ $course->title }}
    </a>

    <x-page-header :title="__('Discussions')" :subtitle="$lesson ? __('About the lesson “:title”', ['title' => $lesson->title]) : __('Ask questions and help each other with this course. Instructors are notified of new questions.')" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="scrollbar-none -mx-1 mb-4 flex gap-2 overflow-x-auto px-1">
                @foreach (['all' => __('All'), 'unanswered' => __('Unanswered'), 'mine' => __('My questions')] as $key => $label)
                    <a href="{{ route('lms.discussions.index', array_filter([$course, 'filter' => $key === 'all' ? null : $key, 'lesson' => $lesson?->id])) }}"
                       @if ($filter === $key) aria-current="page" @endif
                       class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium {{ $filter === $key ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">{{ $label }}</a>
                @endforeach
                @if ($lesson)
                    <a href="{{ route('lms.discussions.index', $course) }}" class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium text-brand-700 hover:underline">{{ __('All lessons') }}</a>
                @endif
            </div>

            @if ($discussions->isEmpty())
                <x-empty-state icon="chat" :title="__('No discussions yet')" :description="__('Be the first to ask a question — the form is right here.')" />
            @else
                <x-card :padded="false" class="overflow-hidden">
                    <ul class="divide-y divide-slate-100">
                        @foreach ($discussions as $discussion)
                            <li>
                                <a href="{{ route('lms.discussions.show', [$course, $discussion]) }}" class="flex items-start gap-4 px-5 py-4 hover:bg-slate-50">
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $discussion->isResolved() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        <x-icon :name="$discussion->isResolved() ? 'check' : 'chat'" class="h-4 w-4" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            @if ($discussion->is_pinned)<x-badge color="brand">{{ __('Pinned') }}</x-badge>@endif
                                            @if ($discussion->is_locked)<x-badge color="slate">{{ __('Closed') }}</x-badge>@endif
                                            <span class="font-semibold text-slate-900">{{ $discussion->title }}</span>
                                        </span>
                                        <span class="mt-1 block text-xs text-slate-500">
                                            {{ $discussion->author?->name ?? __('Former member') }} &middot; {{ $discussion->created_at->diffForHumans() }}
                                            @if ($discussion->lesson) &middot; {{ $discussion->lesson->title }} @endif
                                        </span>
                                    </span>
                                    <span class="shrink-0 text-right text-xs text-slate-500">
                                        <span class="block text-sm font-semibold text-slate-700">{{ $discussion->replies_count }}</span>
                                        {{ trans_choice('reply|replies', $discussion->replies_count) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
                <div class="mt-4">{{ $discussions->links() }}</div>
            @endif
        </div>

        <x-card class="h-fit">
            <h2 class="text-base font-semibold text-slate-900">{{ __('Ask a question') }}</h2>
            <form method="POST" action="{{ route('lms.discussions.store', $course) }}" class="mt-4 space-y-4">
                @csrf
                <x-input type="text" name="title" :label="__('Question')" value="{{ old('title') }}" required minlength="5" maxlength="200" :placeholder="__('e.g. How often should I change my password?')" />
                <x-textarea name="body" :label="__('Details')" rows="5" required minlength="10" maxlength="5000" :placeholder="__('What have you tried? What\'s unclear?')">{{ old('body') }}</x-textarea>
                @if ($lessons->isNotEmpty())
                    <div>
                        <label for="discussion-lesson" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('About a lesson') }} <span class="font-normal text-slate-500">{{ __('(optional)') }}</span></label>
                        <select id="discussion-lesson" name="lesson_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                            <option value="">{{ __('The whole course') }}</option>
                            @foreach ($lessons as $item)
                                <option value="{{ $item->id }}" @selected((string) old('lesson_id', $lesson?->id) === (string) $item->id)>{{ $item->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <x-button type="submit" class="w-full" icon="chat">{{ __('Post question') }}</x-button>
            </form>
        </x-card>
    </div>
</x-app-layout>
