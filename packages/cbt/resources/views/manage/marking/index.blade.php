<x-app-layout title="Marking">
    <x-page-header title="Marking" subtitle="Written answers waiting for a mark. Scores and certificates are held until every essay in an attempt is marked." />

    @if ($attempts->isEmpty())
        <x-empty-state icon="check" title="Nothing to mark" description="When someone submits an exam with essay questions, it will appear here." />
    @else
        <div class="space-y-6">
            @foreach ($attempts as $examTitle => $group)
                <x-card :padded="false" class="overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ $examTitle }}</p>
                        <x-badge color="amber">{{ $group->count() }} to mark</x-badge>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($group as $attempt)
                            <li>
                                <a href="{{ route('cbt.manage.marking.show', $attempt) }}" class="flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-slate-50">
                                    <span>
                                        <span class="block text-sm font-medium text-slate-900">{{ $attempt->user->name }}</span>
                                        <span class="block text-xs text-slate-500">Submitted {{ $attempt->submitted_at->diffForHumans() }} &middot; {{ $attempt->submitted_at->format('M j, Y g:ia') }}</span>
                                    </span>
                                    <span class="shrink-0 text-sm font-semibold text-brand-700">Mark &rarr;</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
