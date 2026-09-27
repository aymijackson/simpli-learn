<x-app-layout title="Discussions">
    <x-page-header title="Discussions" subtitle="Questions from learners in your courses. Reply from the discussion itself — replies from instructors and owners are marked." />

    <div class="mb-4 flex gap-2">
        <a href="{{ route('lms.manage.discussions.index') }}" @if ($waiting) aria-current="page" @endif
           class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium {{ $waiting ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">
            Waiting for a reply <span class="rounded-full px-1.5 text-xs {{ $waiting ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">{{ $waitingCount }}</span>
        </a>
        <a href="{{ route('lms.manage.discussions.index', ['show' => 'all']) }}" @unless ($waiting) aria-current="page" @endunless
           class="rounded-full px-3 py-1.5 text-sm font-medium {{ ! $waiting ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">All discussions</a>
    </div>

    @if ($discussions->isEmpty())
        <x-empty-state icon="chat" :title="$waiting ? 'All caught up' : 'No discussions yet'" :description="$waiting ? 'Every question has a reply from an instructor or owner.' : 'Questions learners ask in your courses will appear here.'" />
    @else
        <x-card :padded="false" class="overflow-hidden">
            <ul class="divide-y divide-slate-100">
                @foreach ($discussions as $discussion)
                    <li>
                        <a href="{{ route('lms.discussions.show', [$discussion->course, $discussion]) }}" class="flex items-start justify-between gap-4 px-6 py-4 hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $discussion->title }}</span>
                                <span class="block text-xs text-slate-500">
                                    {{ $discussion->course?->title }}@if ($discussion->lesson) &middot; {{ $discussion->lesson->title }}@endif
                                    &middot; {{ $discussion->author?->name ?? 'Former member' }} &middot; {{ $discussion->created_at->diffForHumans() }}
                                </span>
                            </span>
                            <span class="flex shrink-0 items-center gap-2">
                                @if ($discussion->isResolved())
                                    <x-badge color="green">Answered</x-badge>
                                @elseif (! $discussion->staff_replied_at)
                                    <x-badge color="amber">Needs a reply</x-badge>
                                @endif
                                <span class="text-xs text-slate-500">{{ $discussion->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $discussion->replies_count) }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-card>
        <div class="mt-4">{{ $discussions->links() }}</div>
    @endif
</x-app-layout>
