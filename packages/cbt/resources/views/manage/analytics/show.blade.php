<x-app-layout :title="$exam->title.' Analytics'">
    <a href="{{ route('cbt.manage.analytics.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; All exams
    </a>

    <x-page-header :title="$exam->title" subtitle="Results and shortlisting.">
        <x-slot:actions>
            <x-button :href="route('cbt.manage.analytics.people', $exam)" variant="secondary" icon="users">Results by person</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('cbt.manage.analytics.show', $exam) }}" class="grid gap-4 sm:grid-cols-5 sm:items-end">
            <x-input type="number" name="min_score" label="Min score" value="{{ $filters['min_score'] ?? '' }}" min="0" max="100" />
            <x-input type="number" name="max_score" label="Max score" value="{{ $filters['max_score'] ?? '' }}" min="0" max="100" />
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Result</label>
                <select name="status" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                    <option value="">Any</option>
                    <option value="passed" @selected(($filters['status'] ?? '') === 'passed')>Passed</option>
                    <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed</option>
                </select>
            </div>
            <x-input type="date" name="from" label="Submitted from" value="{{ $filters['from'] ?? '' }}" />
            <x-input type="date" name="to" label="Submitted to" value="{{ $filters['to'] ?? '' }}" />
            <div class="sm:col-span-5 flex items-center gap-3">
                <x-button type="submit" variant="secondary">Apply filters</x-button>
                @if (array_filter($filters))
                    <a href="{{ route('cbt.manage.analytics.show', $exam) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Clear</a>
                @endif
                <span class="ml-auto flex gap-3">
                    <a href="{{ route('cbt.manage.analytics.export.csv', array_merge(['exam' => $exam], $filters)) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Export CSV</a>
                    <a href="{{ route('cbt.manage.analytics.export.pdf', array_merge(['exam' => $exam], $filters)) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Export PDF</a>
                </span>
            </div>
        </form>
    </x-card>

    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $stats['count'] }}</p>
            <p class="text-xs text-slate-500">Attempts shown</p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $stats['uniqueLearners'] }}</p>
            <p class="text-xs text-slate-500">Learners</p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $stats['passRate'] ?? '—' }}{{ ! is_null($stats['passRate']) ? '%' : '' }}</p>
            <p class="text-xs text-slate-500">Pass rate</p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $stats['avgScore'] ?? '—' }}{{ ! is_null($stats['avgScore']) ? '%' : '' }}</p>
            <p class="text-xs text-slate-500">Avg score ({{ $stats['minScore'] ?? '—' }}&ndash;{{ $stats['maxScore'] ?? '—' }})</p>
        </x-card>
    </div>

    <p class="mb-4 text-sm text-slate-500">
        Time taken: min {{ $stats['minMinutes'] ?? '—' }} &middot; avg {{ $stats['avgMinutes'] ?? '—' }} &middot; max {{ $stats['maxMinutes'] ?? '—' }} minutes
    </p>

    @if ($attempts->isEmpty())
        <x-empty-state title="No matching attempts" description="Try widening your filters." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($attempts as $attempt)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $attempt->user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $attempt->user->email }} &middot; submitted {{ $attempt->submitted_at->format('M j, Y g:ia') }} &middot; {{ round($attempt->durationMinutes(), 1) }} min</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($attempt->integrity_events_count > 0)
                                <a href="{{ route('cbt.manage.analytics.attempts.integrity', [$exam, $attempt]) }}" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 hover:bg-amber-100">
                                    &#9888; {{ $attempt->integrity_events_count }} {{ \Illuminate\Support\Str::plural('flag', $attempt->integrity_events_count) }}
                                </a>
                            @endif
                            @if ($attempt->isAwaitingMarking())
                                <a href="{{ route('cbt.manage.marking.show', $attempt) }}"><x-badge color="amber">Awaiting marking</x-badge></a>
                            @else
                                <x-badge :color="$attempt->passed() ? 'green' : 'red'">{{ $attempt->score }}% &middot; {{ $attempt->passed() ? 'Passed' : 'Failed' }}</x-badge>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <div class="mt-4">
            {{ $attempts->links() }}
        </div>
    @endif
</x-app-layout>
