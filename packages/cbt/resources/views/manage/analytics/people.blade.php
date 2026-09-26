@use('Elibrary\Cbt\Http\Controllers\Manage\ExamPeopleReportController', 'Report')
@php
    $colors = ['passed' => 'green', 'failed' => 'red', 'in_progress' => 'amber', 'not_attempted' => 'slate'];
    $filters = ['all' => 'Everyone'] + Report::LABELS;
@endphp

<x-app-layout :title="$exam->title.' — results by person'">
    <a href="{{ route('cbt.manage.analytics.show', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> {{ $exam->title }} analytics
    </a>

    <x-page-header :title="$exam->title" eyebrow="Results by person" :subtitle="'Everyone in the workspace against this exam — pass mark '.$exam->pass_percentage.'%.'">
        <x-slot:actions>
            <x-button :href="route('cbt.manage.analytics.people.export', $exam)" variant="secondary" icon="download">Export CSV</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card>
            <p class="text-sm font-medium text-slate-500">Pass rate</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $passRate }}%</p>
            <x-progress class="mt-3" :value="$passRate" tone="emerald" />
            <p class="mt-2 text-xs text-slate-400">{{ $counts['passed'] }} of {{ $counts['all'] }} people</p>
        </x-card>
        <x-stat-card label="Not passed yet" :value="$counts['failed']" icon="x-mark" tone="rose" hint="Attempted, below the pass mark" />
        <x-stat-card label="In progress" :value="$counts['in_progress']" icon="clock" tone="amber" />
        <x-stat-card label="Not attempted" :value="$counts['not_attempted']" icon="flag" tone="slate" />
    </div>

    <div class="scrollbar-none -mx-1 mb-4 flex gap-2 overflow-x-auto px-1">
        @foreach ($filters as $key => $label)
            <a href="{{ route('cbt.manage.analytics.people', $key === 'all' ? $exam : [$exam, 'status' => $key]) }}"
               class="inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium {{ $activeFilter === $key ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">
                {{ $label }}
                <span class="rounded-full px-1.5 text-xs {{ $activeFilter === $key ? 'bg-white/20' : 'bg-slate-100 text-slate-500' }}">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    @if ($rows->isEmpty())
        <x-empty-state icon="users" title="Nobody here" description="No one in the workspace matches this filter." />
    @else
        <x-card :padded="false" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Person</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Best score</th>
                            <th class="px-5 py-3">Attempts</th>
                            <th class="px-5 py-3">Passed on</th>
                            <th class="px-5 py-3">Last attempt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('tenant.team.show', $row['user']) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $row['user']->name }}</a>
                                    <p class="text-xs text-slate-500">{{ $row['user']->email }}</p>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap"><x-badge :color="$colors[$row['status']]">{{ Report::LABELS[$row['status']] }}</x-badge></td>
                                <td class="px-5 py-3 whitespace-nowrap font-semibold {{ $row['best'] === null ? 'text-slate-400' : ($row['best'] >= $exam->pass_percentage ? 'text-emerald-600' : 'text-rose-600') }}">
                                    {{ $row['best'] === null ? '—' : $row['best'].'%' }}
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $row['attempts'] ?: '—' }}</td>
                                <td class="px-5 py-3 whitespace-nowrap text-slate-600">{{ $row['passed_at']?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-3 whitespace-nowrap text-slate-600">{{ $row['last_at']?->format('M j, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-app-layout>
