<x-admin-layout title="Shared courses">
    <x-page-header title="Shared courses" subtitle="Open a course to every workspace, or share it with chosen ones. The owning workspace keeps sole control of its content." />

    <form method="GET" action="{{ route('admin.shared-courses.index') }}" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search courses&hellip;"
               class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:max-w-xs sm:text-sm">
        @if ($filter === 'shared')<input type="hidden" name="filter" value="shared">@endif
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach (['all' => 'All courses', 'shared' => 'Shared only'] as $key => $label)
            <a href="{{ route('admin.shared-courses.index', array_filter(['q' => $search, 'filter' => $key === 'all' ? null : $key])) }}"
               class="rounded-full px-3 py-1 text-sm font-medium {{ $filter === $key ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($courses->isEmpty())
        <x-empty-state icon="academic-cap" title="No courses found" description="Courses appear here once workspaces create them." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($courses as $course)
                    @php($sharedIds = $course->sharedWorkspaces->pluck('id'))
                    <li class="px-6 py-4">
                        <details class="group">
                            <summary class="flex cursor-pointer items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $course->title }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $course->tenant?->name ?? 'Unknown workspace' }} &middot; {{ $course->lessons_count }} {{ \Illuminate\Support\Str::plural('lesson', $course->lessons_count) }}
                                        @unless ($course->is_published) &middot; <span class="text-amber-700">draft — not visible anywhere until published</span> @endunless
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($course->is_public)
                                        <x-badge color="green" icon="globe">All workspaces</x-badge>
                                    @elseif ($sharedIds->isNotEmpty())
                                        <x-badge color="brand">{{ $sharedIds->count() }} {{ \Illuminate\Support\Str::plural('workspace', $sharedIds->count()) }}</x-badge>
                                    @else
                                        <x-badge color="slate">Private</x-badge>
                                    @endif
                                    <x-icon name="chevron-down" class="details-chevron h-4 w-4 text-slate-500 transition" />
                                </div>
                            </summary>

                            <form method="POST" action="{{ route('admin.shared-courses.update', $course->id) }}" class="mt-4 space-y-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                @csrf
                                @method('PUT')
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" name="is_public" value="1" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked($course->is_public)>
                                    <span>
                                        <span class="font-medium text-slate-700">Open to every workspace</span>
                                        <span class="block text-xs text-slate-500">The workspace owner can also turn this on or off.</span>
                                    </span>
                                </label>
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Or share with these workspaces</p>
                                    <div class="mt-2 grid max-h-48 gap-1 overflow-y-auto rounded-lg bg-white p-2 ring-1 ring-slate-200 sm:grid-cols-2">
                                        @foreach ($workspaces->where('id', '!=', $course->tenant_id) as $workspace)
                                            <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50">
                                                <input type="checkbox" name="workspace_ids[]" value="{{ $workspace->id }}" class="rounded text-brand-600 focus:ring-brand-600" @checked($sharedIds->contains($workspace->id))>
                                                <span class="truncate">{{ $workspace->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500">Learners elsewhere take it free, with their progress kept in their own workspace. Exams inside the course need the Testing module in their workspace.</p>
                                <x-button type="submit" size="sm">Save sharing</x-button>
                            </form>
                        </details>
                    </li>
                @endforeach
            </ul>
        </x-card>
        <div class="mt-4">{{ $courses->links() }}</div>
    @endif
</x-admin-layout>
