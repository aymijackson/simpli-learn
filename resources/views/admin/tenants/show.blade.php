<x-admin-layout :title="$workspace->name">
    <a href="{{ route('admin.tenants.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to tenants
    </a>

    <x-page-header :title="$workspace->name" :subtitle="'/t/'.$workspace->slug">
        <x-slot:actions>
            <x-badge :color="$workspace->status->badgeColor()">{{ $workspace->status->label() }}</x-badge>
            @if ($workspace->isActive())
                <form method="POST" action="{{ route('admin.tenants.impersonate', $workspace) }}">
                    @csrf
                    <x-button type="submit">Manage this workspace</x-button>
                </form>
                <form method="POST" action="{{ route('admin.tenants.suspend', $workspace) }}" onsubmit="return confirm('Suspend this workspace? It will become unreachable until reactivated.')">
                    @csrf
                    <x-button type="submit" variant="secondary">Suspend</x-button>
                </form>
            @elseif ($workspace->status === \App\Enums\TenantStatus::Suspended)
                <form method="POST" action="{{ route('admin.tenants.reactivate', $workspace) }}">
                    @csrf
                    <x-button type="submit">Reactivate</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-8 lg:grid-cols-2">
        <div>
            <h2 class="mb-4 text-sm font-semibold text-slate-900">Packages</h2>
            <x-card>
                <form method="POST" action="{{ route('admin.tenants.modules', $workspace) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @foreach ($modules as $module)
                        @php($enabled = $workspace->hasModule($module))
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                            <input type="checkbox" name="modules[]" value="{{ $module->value }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked($enabled)>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $module->softClasses() }}">
                                <x-module-icon :module="$module" class="h-4 w-4" />
                            </div>
                            <span class="text-sm font-medium text-slate-900">{{ $module->label() }}</span>
                        </label>
                    @endforeach
                    <x-button type="submit">Save packages</x-button>
                </form>
            </x-card>
        </div>

        <div>
            <h2 class="mb-4 text-sm font-semibold text-slate-900">Users</h2>
            @if ($users->isEmpty())
                <x-empty-state title="No users yet" />
            @else
                <x-card :padded="false">
                    <ul class="divide-y divide-slate-200">
                        @foreach ($users as $user)
                            <li class="flex items-center justify-between gap-4 px-6 py-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                                </div>
                                <x-badge :color="$user->isOwner() ? 'indigo' : 'slate'">{{ $user->role->label() }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </div>
    </div>
</x-admin-layout>
