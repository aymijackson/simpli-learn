{{-- Shared activity-log listing. Expects $entries, $categories, $activeCategory, $search, $formAction, $showWorkspace. --}}
<form method="GET" action="{{ $formAction }}" class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="scrollbar-none -mx-1 flex gap-2 overflow-x-auto px-1">
        <a href="{{ $formAction.($search !== '' ? '?q='.urlencode($search) : '') }}"
           class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium {{ $activeCategory === '' ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">All</a>
        @foreach ($categories as $key => [$label, $icon])
            <a href="{{ $formAction.'?'.http_build_query(array_filter(['category' => $key, 'q' => $search])) }}"
               class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium {{ $activeCategory === $key ? 'bg-ink-900 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">
                <x-icon :name="$icon" class="h-3.5 w-3.5" /> {{ $label }}
            </a>
        @endforeach
    </div>
    <div class="flex gap-2">
        @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
        <label class="relative">
            <span class="sr-only">Search the log</span>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="q" value="{{ $search }}" placeholder="Search person or action"
                   class="block w-64 rounded-lg border-0 py-2 pr-3 pl-9 text-sm text-slate-900 ring-1 ring-slate-300 ring-inset focus:ring-2 focus:ring-brand-600">
        </label>
        <x-button type="submit" variant="secondary" size="sm">Search</x-button>
    </div>
</form>

@if ($entries->isEmpty())
    <x-empty-state icon="list" title="No activity recorded" :description="$search !== '' || $activeCategory ? 'Nothing matches these filters.' : 'Sign-ins, team changes, payments and settings changes will appear here.'" />
@else
    <x-card :padded="false" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">When</th>
                        <th class="px-5 py-3">Who</th>
                        @if ($showWorkspace)<th class="px-5 py-3">Workspace</th>@endif
                        <th class="px-5 py-3">What happened</th>
                        <th class="px-5 py-3">IP address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($entries as $entry)
                        @php([$label, $icon] = $categories[\Illuminate\Support\Str::before($entry->action, '.')] ?? ['Other', 'list'])
                        <tr class="{{ $entry->action === 'auth.failed' ? 'bg-rose-50/50' : '' }}">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500" title="{{ $entry->created_at->toDayDateTimeString() }}">
                                {{ $entry->created_at->format('M j, Y') }}<br><span class="text-xs">{{ $entry->created_at->format('g:i a') }}</span>
                            </td>
                            <td class="px-5 py-3 font-medium whitespace-nowrap text-slate-900">{{ $entry->actor_name ?? 'Someone (not signed in)' }}</td>
                            @if ($showWorkspace)
                                <td class="px-5 py-3 whitespace-nowrap text-slate-600">{{ $entry->tenant?->name ?? 'Central' }}</td>
                            @endif
                            <td class="px-5 py-3 text-slate-700">
                                <span class="inline-flex items-center gap-2">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $entry->action === 'auth.failed' ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500' }}" title="{{ $label }}">
                                        <x-icon :name="$icon" class="h-3.5 w-3.5" />
                                    </span>
                                    {{ $entry->description }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs whitespace-nowrap text-slate-500">{{ $entry->ip_address ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
    <div class="mt-6">{{ $entries->links() }}</div>
    <p class="mt-4 text-xs text-slate-400">Entries are kept for {{ \App\Models\ActivityLog::RETENTION_DAYS / 365 }} years.</p>
@endif
