<x-admin-layout title="Dashboard">
    <x-page-header title="Dashboard" subtitle="An overview of every workspace on the platform." />

    <div class="mb-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card>
            <p class="text-sm text-slate-500">Pending approval</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $counts['pending'] }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-slate-500">Active</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $counts['active'] }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-slate-500">Suspended</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $counts['suspended'] }}</p>
        </x-card>
        <x-card>
            <p class="text-sm text-slate-500">Rejected</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $counts['rejected'] }}</p>
        </x-card>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">
            Needs your review
            @if ($pendingTenants->isNotEmpty())
                <span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">{{ $pendingTenants->count() }}</span>
            @endif
        </h2>
        <a href="{{ route('admin.tenants.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">View all tenants &rarr;</a>
    </div>

    @if ($pendingTenants->isEmpty())
        <x-empty-state title="No pending signups" description="New workspace registrations will show up here for review." />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($pendingTenants as $tenant)
                <x-card>
                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-sm font-semibold text-slate-900 hover:text-brand-600">{{ $tenant->name }}</a>
                    <p class="text-sm text-slate-500">/t/{{ $tenant->slug }}</p>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($tenant->tenantModules as $tenantModule)
                            <x-badge color="indigo">{{ $tenantModule->module->shortLabel() }}</x-badge>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.tenants.approve', $tenant) }}">
                            @csrf
                            <x-button type="submit" variant="primary">Approve</x-button>
                        </form>
                        <form method="POST" action="{{ route('admin.tenants.reject', $tenant) }}">
                            @csrf
                            <x-button type="submit" variant="secondary">Reject</x-button>
                        </form>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-admin-layout>
