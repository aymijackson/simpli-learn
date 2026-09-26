@php
    $adminNav = [
        'Overview' => [
            ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ['route' => 'admin.activity', 'match' => 'admin.activity', 'icon' => 'list', 'label' => 'Activity log'],
        ],
        'Workspaces' => [
            ['route' => 'admin.tenants.index', 'match' => 'admin.tenants.*', 'icon' => 'building', 'label' => 'Tenants', 'badge' => $pendingTenants ?: null],
        ],
        'Website' => [
            ['route' => 'admin.pages.index', 'match' => 'admin.pages.*', 'icon' => 'document', 'label' => 'Pages'],
            ['route' => 'home', 'match' => null, 'icon' => 'globe', 'label' => 'View website'],
        ],
        'Settings' => [
            ['route' => 'admin.payment-settings.edit', 'match' => 'admin.payment-settings.*', 'icon' => 'credit-card', 'label' => 'Payments'],
            ['route' => 'profile.edit', 'match' => 'profile.*', 'icon' => 'user-circle', 'label' => 'Profile & security'],
        ],
    ];
@endphp

{{-- Platform-admin navigation, rendered in both the desktop sidebar and the mobile drawer. --}}
<div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/5 px-5">
    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-700 font-display text-base font-bold text-white">e</span>
    <div>
        <p class="text-sm font-semibold text-white">e-Library Central</p>
        <p class="text-xs text-slate-500">Platform admin</p>
    </div>
</div>

<nav class="flex-1 overflow-y-auto px-3 pb-6">
    @foreach ($adminNav as $section => $items)
        <x-sidebar-section :title="$section">
            @foreach ($items as $item)
                <x-sidebar-link :href="route($item['route'])" :icon="$item['icon']" :badge="$item['badge'] ?? null"
                                :active="$item['match'] && request()->routeIs($item['match'])">{{ $item['label'] }}</x-sidebar-link>
            @endforeach
        </x-sidebar-section>
    @endforeach
</nav>

<div class="border-t border-white/5 p-3">
    <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500/20 text-sm font-bold text-brand-200">{{ $initials ?: '?' }}</span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-lg p-1.5 text-slate-500 hover:bg-white/10 hover:text-white" title="Log out" aria-label="Log out">
                <x-icon name="logout" class="h-4 w-4" />
            </button>
        </form>
    </div>
</div>
