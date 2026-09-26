@use('Elibrary\Lms\Reports\CourseProgressReport', 'Report')
@php
    $statusColors = [
        Report::COMPLETED => 'green',
        Report::FINAL_PENDING => 'amber',
        Report::IN_PROGRESS => 'sky',
        Report::NOT_STARTED => 'slate',
        Report::NOT_ENROLLED => 'slate',
    ];
    $filters = ['all' => 'Everyone', 'overdue' => 'Overdue'] + Report::LABELS;
@endphp

<x-app-layout :title="$course->title.' — completion'">
    <a href="{{ route('lms.manage.courses.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> Courses
    </a>

    <x-page-header :title="$course->title" subtitle="Who has completed this course, who is part-way through, and who hasn't started." eyebrow="Completion report">
        <x-slot:actions>
            <x-button :href="route('lms.manage.courses.report.export', $course)" variant="secondary" icon="download">Export CSV</x-button>
            <x-button :href="route('lms.manage.courses.edit', $course)" variant="secondary" icon="pencil">Edit course</x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Headline numbers --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card>
            <p class="text-sm font-medium text-slate-500">Completion rate</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $completionRate }}%</p>
            <x-progress class="mt-3" :value="$completionRate" tone="emerald" />
            <p class="mt-2 text-xs text-slate-400">{{ $counts[Report::COMPLETED] }} of {{ $counts['all'] }} people in the workspace</p>
        </x-card>
        <x-stat-card label="In progress" :value="$counts[Report::IN_PROGRESS] + $counts[Report::FINAL_PENDING]" icon="play" tone="sky"
                     :hint="$counts[Report::FINAL_PENDING] ? $counts[Report::FINAL_PENDING].' waiting on the final exam' : null" />
        <x-stat-card label="Not started" :value="$counts[Report::NOT_STARTED] + $counts[Report::NOT_ENROLLED]" icon="clock" tone="slate"
                     :hint="$counts[Report::NOT_ENROLLED].' not enrolled'" />
        <x-stat-card label="Overdue" :value="$counts['overdue']" icon="flag" :tone="$counts['overdue'] ? 'rose' : 'slate'" hint="Past their due date" />
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-inset ring-red-600/20">{{ $errors->first() }}</div>
    @endif

    {{-- Assign --}}
    <x-card class="mb-6">
        <details @if ($errors->any()) open @endif>
            <summary class="flex cursor-pointer items-center justify-between gap-4">
                <span>
                    <span class="block text-base font-semibold text-slate-900">Assign this course</span>
                    <span class="block text-sm text-slate-500">People are enrolled straight away, emailed, and reminded as the due date approaches.</span>
                </span>
                <span class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white">
                    <x-icon name="plus" class="h-4 w-4" /> Assign
                </span>
            </summary>

            <form method="POST" action="{{ route('lms.manage.courses.assign', $course) }}" class="mt-6 space-y-5 border-t border-slate-100 pt-6">
                @csrf
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-slate-700">Who</legend>
                    <div class="flex flex-wrap gap-3">
                        <label class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                            <input type="radio" name="who" value="everyone" class="text-brand-600 focus:ring-brand-600" @checked(old('who', 'everyone') === 'everyone')>
                            Everyone in the workspace ({{ $allRows->count() }})
                        </label>
                        <label class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                            <input type="radio" name="who" value="selected" class="text-brand-600 focus:ring-brand-600" @checked(old('who') === 'selected')>
                            Selected people
                        </label>
                    </div>
                    <div class="mt-3 grid max-h-64 gap-1 overflow-y-auto rounded-lg p-2 ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($allRows as $row)
                            <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50">
                                <input type="checkbox" name="user_ids[]" value="{{ $row['user']->id }}" class="rounded text-brand-600 focus:ring-brand-600"
                                       @checked(in_array($row['user']->id, old('user_ids', [])))>
                                <span class="truncate">{{ $row['user']->name }}</span>
                                @if ($row['assignment'])<span class="shrink-0 text-xs text-slate-400">assigned</span>@endif
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Ticking people only applies when "Selected people" is chosen. Re-assigning someone updates their due date.</p>
                </fieldset>

                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <label for="due_at" class="mb-1.5 block text-sm font-medium text-slate-700">Due date <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="due_at" type="date" name="due_at" value="{{ old('due_at') }}" min="{{ now()->toDateString() }}"
                               class="block rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                    </div>
                    <x-button type="submit" icon="check">Assign and email</x-button>
                </div>
            </form>
        </details>
    </x-card>

    {{-- Filters --}}
    <div class="scrollbar-none -mx-1 mb-4 flex gap-2 overflow-x-auto px-1">
        @foreach ($filters as $key => $label)
            <a href="{{ route('lms.manage.courses.report', $key === 'all' ? $course : [$course, 'status' => $key]) }}"
               class="inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium {{ $activeFilter === $key ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">
                {{ $label }}
                <span class="rounded-full px-1.5 text-xs {{ $activeFilter === $key ? 'bg-white/20' : 'bg-slate-100 text-slate-500' }}">{{ $counts[$key] ?? 0 }}</span>
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
                            <th class="px-5 py-3">Progress</th>
                            <th class="px-5 py-3">Due</th>
                            <th class="px-5 py-3">Completed</th>
                            <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr class="{{ $row['overdue'] ? 'bg-rose-50/40' : '' }}">
                                <td class="px-5 py-3">
                                    <a href="{{ route('tenant.team.show', $row['user']) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $row['user']->name }}</a>
                                    <p class="text-xs text-slate-500">{{ $row['user']->email }}</p>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <x-badge :color="$statusColors[$row['status']]">{{ Report::LABELS[$row['status']] }}</x-badge>
                                    @if ($row['overdue'])
                                        <x-badge color="red">Overdue</x-badge>
                                    @endif
                                </td>
                                <td class="w-48 px-5 py-3">
                                    @if ($row['status'] !== Report::NOT_ENROLLED)
                                        <x-progress :value="$row['progress']" label />
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap {{ $row['overdue'] ? 'font-medium text-rose-700' : 'text-slate-600' }}">
                                    {{ $row['assignment']?->due_at?->format('M j, Y') ?? ($row['assignment'] ? 'No due date' : '—') }}
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-slate-600">{{ $row['completed_at']?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    @if ($row['assignment'])
                                        <form method="POST" action="{{ route('lms.manage.courses.unassign', [$course, $row['assignment']]) }}" onsubmit="return confirm('Remove this assignment? They stay enrolled and keep their progress.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-slate-500 hover:text-red-600">Unassign</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-app-layout>
