<x-admin-layout title="Tenants">
    <x-page-header title="Tenants" subtitle="Every workspace on the platform." />

    <form method="GET" action="{{ route('admin.tenants.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            type="text"
            name="q"
            value="{{ $search }}"
            placeholder="Search by name or slug&hellip;"
            class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:max-w-xs sm:text-sm"
        >
        @if ($activeStatus)
            <input type="hidden" name="status" value="{{ $activeStatus }}">
        @endif
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>

    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.tenants.index', array_filter(['q' => $search])) }}"
           class="rounded-full px-3 py-1 text-sm font-medium {{ $activeStatus === '' ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            All
        </a>
        @foreach ($statuses as $status)
            <a href="{{ route('admin.tenants.index', array_filter(['q' => $search, 'status' => $status->value])) }}"
               class="rounded-full px-3 py-1 text-sm font-medium {{ $activeStatus === $status->value ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $status->label() }}
            </a>
        @endforeach
    </div>

    @if ($tenants->isEmpty())
        <x-empty-state title="No workspaces found" description="Try a different search term or status filter." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($tenants as $tenant)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-sm font-semibold text-slate-900 hover:text-brand-600">{{ $tenant->name }}</a>
                            <p class="text-sm text-slate-500">/t/{{ $tenant->slug }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="hidden flex-wrap justify-end gap-1.5 sm:flex">
                                @foreach ($tenant->tenantModules as $tenantModule)
                                    <x-badge color="slate">{{ $tenantModule->module->shortLabel() }}</x-badge>
                                @endforeach
                            </div>
                            <x-badge :color="$tenant->status->badgeColor()">{{ $tenant->status->label() }}</x-badge>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-admin-layout>
