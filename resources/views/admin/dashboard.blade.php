<x-admin-layout title="Dashboard">
    <x-page-header title="Platform overview" subtitle="Every workspace on e-Library, and the signups waiting for your review." />

    <div class="mb-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Pending approval" :value="$counts['pending']" icon="clock" tone="amber" :href="route('admin.tenants.index')" />
        <x-stat-card label="Active" :value="$counts['active']" icon="check-circle" tone="emerald" :href="route('admin.tenants.index')" />
        <x-stat-card label="Suspended" :value="$counts['suspended']" icon="lock" tone="slate" :href="route('admin.tenants.index')" />
        <x-stat-card label="Rejected" :value="$counts['rejected']" icon="x-mark" tone="rose" :href="route('admin.tenants.index')" />
    </div>

    {{-- System health: the same checks /health reports to uptime monitors. --}}
    @php($allHealthy = collect($health)->every(fn ($check) => $check['ok']))
    <x-card class="mb-10">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                <x-icon :name="$allHealthy ? 'check-circle' : 'flag'" class="h-5 w-5 {{ $allHealthy ? 'text-emerald-600' : 'text-red-600' }}" />
                System health
            </h2>
            <p class="text-xs text-slate-500">Uptime monitors can watch <a href="{{ route('health') }}" class="font-mono text-brand-700 hover:underline">{{ route('health') }}</a></p>
        </div>
        <ul class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($health as $name => $check)
                <li class="rounded-xl px-4 py-3 ring-1 {{ $check['ok'] ? 'bg-emerald-50/60 ring-emerald-200' : 'bg-red-50 ring-red-200' }}">
                    <p class="flex items-center gap-2 text-sm font-semibold {{ $check['ok'] ? 'text-emerald-800' : 'text-red-800' }}">
                        <x-icon :name="$check['ok'] ? 'check' : 'x-mark'" class="h-4 w-4" /> {{ ucfirst($name) }}
                    </p>
                    <p class="mt-0.5 text-xs {{ $check['ok'] ? 'text-emerald-800' : 'text-red-800' }}">{{ $check['note'] }}</p>
                </li>
            @endforeach
        </ul>
        <p class="mt-4 text-xs text-slate-500">
            Error alerts are <strong>{{ config('monitoring.alerts_enabled') ? 'on' : 'off' }}</strong>
            @if (config('monitoring.alerts_enabled'))
                and go to {{ implode(', ', \App\Support\Monitoring\ErrorAlerter::recipients()) ?: 'nobody yet' }}.
            @else
                — set MONITORING_ALERTS=true in .env to turn them on.
            @endif
            Test with <code class="font-mono">php artisan monitoring:test-alert</code>.
        </p>
    </x-card>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
            Needs your review
            @if ($pendingTenants->isNotEmpty())
                <x-badge color="amber">{{ $pendingTenants->count() }}</x-badge>
            @endif
        </h2>
        <a href="{{ route('admin.tenants.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-600">View all tenants &rarr;</a>
    </div>

    @if ($pendingTenants->isEmpty())
        <x-empty-state icon="check-circle" title="No pending signups" description="New workspace registrations will show up here for review." />
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($pendingTenants as $tenant)
                <x-card class="flex flex-col">
                    <div class="flex items-start gap-3">
                        <x-workspace-mark :tenant="$tenant" class="h-10! w-10!" />
                        <div class="min-w-0">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-brand-700">{{ $tenant->name }}</a>
                            <p class="text-xs text-slate-500">/t/{{ $tenant->slug }} &middot; signed up {{ $tenant->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @foreach ($tenant->tenantModules as $tenantModule)
                            <x-badge color="indigo">{{ $tenantModule->module->shortLabel() }}</x-badge>
                        @endforeach
                    </div>

                    <div class="mt-5 flex items-center gap-2 border-t border-slate-100 pt-4">
                        <form method="POST" action="{{ route('admin.tenants.approve', $tenant) }}">
                            @csrf
                            <x-button type="submit" size="sm" icon="check">Approve</x-button>
                        </form>
                        <form method="POST" action="{{ route('admin.tenants.reject', $tenant) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm">Reject</x-button>
                        </form>
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="ml-auto text-sm font-medium text-slate-500 hover:text-slate-800">Details</a>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-admin-layout>
