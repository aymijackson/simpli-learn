<x-app-layout title="Exam Analytics">
    <x-page-header title="Exam analytics" subtitle="Performance across every exam in your workspace." />

    @if ($rows->isEmpty())
        <x-empty-state title="No exams yet" description="Create an exam to see analytics here." />
    @else
        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3">Exam</th>
                            <th class="px-4 py-3 text-right">Attempts</th>
                            <th class="px-4 py-3 text-right">Learners</th>
                            <th class="px-4 py-3 text-right">Pass rate</th>
                            <th class="px-4 py-3 text-right">Score (min / avg / max)</th>
                            <th class="px-4 py-3 text-right">Time taken (min / avg / max)</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-6 py-3 font-medium text-slate-900">{{ $row->exam->title }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row->count }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $row->uniqueLearners }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if (! is_null($row->passRate))
                                        <x-badge :color="$row->passRate >= $row->exam->pass_percentage ? 'green' : 'amber'">{{ $row->passRate }}%</x-badge>
                                    @else
                                        <span class="text-slate-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-600">
                                    @if (! is_null($row->avgScore))
                                        {{ $row->minScore }}% / {{ $row->avgScore }}% / {{ $row->maxScore }}%
                                    @else
                                        <span class="text-slate-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-600">
                                    @if (! is_null($row->avgMinutes))
                                        {{ $row->minMinutes }} / {{ $row->avgMinutes }} / {{ $row->maxMinutes }}
                                    @else
                                        <span class="text-slate-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('cbt.manage.analytics.show', $row->exam) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">View &rarr;</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-app-layout>
