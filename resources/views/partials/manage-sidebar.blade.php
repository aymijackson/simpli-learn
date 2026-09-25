{{-- Owner back-office navigation. Rendered twice by manage-layout (desktop
     sidebar and mobile drawer), so it must not contain element ids. --}}
<div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/5 px-5">
    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-700 font-display text-base font-bold text-white">
        {{ mb_strtoupper(mb_substr($tenant->name, 0, 1)) }}
    </span>
    <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-white">{{ $tenant->name }}</p>
        <p class="text-xs text-slate-500">Workspace admin</p>
    </div>
</div>

<nav class="flex-1 overflow-y-auto px-3 pb-6">
    <x-sidebar-section>
        <div class="pt-4"></div>
        <x-sidebar-link :href="route('tenant.manage.dashboard')" icon="dashboard" :active="request()->routeIs('tenant.manage.dashboard')">Dashboard</x-sidebar-link>
    </x-sidebar-section>

    @if ($hasModule(\App\Enums\Module::Lms))
        <x-sidebar-section title="Learning">
            <x-sidebar-link :href="route('lms.manage.courses.index')" icon="academic-cap" :active="request()->routeIs('lms.manage.courses.*', 'lms.manage.lessons.*', 'lms.manage.modules.*')">Courses</x-sidebar-link>
            <x-sidebar-link :href="route('lms.manage.course-purchases.index')" icon="receipt" :badge="$reviews['lms'] ?? null" :active="request()->routeIs('lms.manage.course-purchases.*')">Course sales</x-sidebar-link>
            <x-sidebar-link :href="route('lms.manage.payment-gateways.edit')" icon="credit-card" :active="request()->routeIs('lms.manage.payment-gateways.*')">Payment gateways</x-sidebar-link>
        </x-sidebar-section>
    @endif

    @if ($hasModule(\App\Enums\Module::Cbt))
        <x-sidebar-section title="Testing">
            <x-sidebar-link :href="route('cbt.manage.exams.index')" icon="clipboard-check" :active="request()->routeIs('cbt.manage.exams.*', 'cbt.manage.questions.*', 'cbt.manage.sections.*')">Exams</x-sidebar-link>
            <x-sidebar-link :href="route('cbt.manage.analytics.index')" icon="chart-bar" :active="request()->routeIs('cbt.manage.analytics.*')">Results &amp; analytics</x-sidebar-link>
            <x-sidebar-link :href="route('cbt.manage.certificates.settings.edit')" icon="trophy" :active="request()->routeIs('cbt.manage.certificates.*')">Certificates</x-sidebar-link>
            <x-sidebar-link :href="route('cbt.manage.certificate-payments.index')" icon="receipt" :badge="$reviews['cbt'] ?? null" :active="request()->routeIs('cbt.manage.certificate-payments.*')">Certificate sales</x-sidebar-link>
            <x-sidebar-link :href="route('cbt.manage.payment-gateways.edit')" icon="credit-card" :active="request()->routeIs('cbt.manage.payment-gateways.*')">Payment gateways</x-sidebar-link>
        </x-sidebar-section>
    @endif

    @if ($hasModule(\App\Enums\Module::Library))
        <x-sidebar-section title="Library">
            <x-sidebar-link :href="route('library.manage.resources.index')" icon="book-open" :active="request()->routeIs('library.manage.resources.*')">Resources</x-sidebar-link>
            <x-sidebar-link :href="route('library.manage.analytics.index')" icon="trending-up" :active="request()->routeIs('library.manage.analytics.*')">Engagement</x-sidebar-link>
            <x-sidebar-link :href="route('library.manage.resource-purchases.index')" icon="receipt" :badge="$reviews['library'] ?? null" :active="request()->routeIs('library.manage.resource-purchases.*')">Resource sales</x-sidebar-link>
            <x-sidebar-link :href="route('library.manage.payment-gateways.edit')" icon="credit-card" :active="request()->routeIs('library.manage.payment-gateways.*')">Payment gateways</x-sidebar-link>
        </x-sidebar-section>
    @endif

    <x-sidebar-section title="Workspace">
        <x-sidebar-link :href="route('tenant.team.index')" icon="users" :active="request()->routeIs('tenant.team.*')">Team &amp; learners</x-sidebar-link>
        <x-sidebar-link :href="route('tenant.home')" icon="eye">View learner site</x-sidebar-link>
    </x-sidebar-section>
</nav>

<div class="border-t border-white/5 p-3">
    <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500/20 text-sm font-bold text-brand-200">
            {{ collect(explode(' ', (string) auth()->user()->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-slate-500">Owner</p>
        </div>
        <form method="POST" action="{{ route('tenant.logout') }}">
            @csrf
            <button type="submit" class="rounded-lg p-1.5 text-slate-500 hover:bg-white/10 hover:text-white" title="Log out" aria-label="Log out">
                <x-icon name="logout" class="h-4 w-4" />
            </button>
        </form>
    </div>
</div>
