<x-app-layout :title="$exam->title.' — Integrity log'">
    <a href="{{ route('cbt.manage.analytics.show', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $exam->title }} results
    </a>

    <x-page-header :title="$attempt->user->name" :subtitle="'Integrity events for this attempt — a signal for your review, not an automatic penalty.'" />

    @if ($events->isEmpty())
        <x-empty-state title="No integrity events recorded" />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($events as $event)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <span class="text-sm text-slate-700">{{ $event->event_type->label() }}</span>
                        <span class="text-xs text-slate-500">{{ $event->created_at->format('M j, Y g:i:s a') }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
