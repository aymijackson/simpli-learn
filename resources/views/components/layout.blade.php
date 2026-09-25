@php
    $user = auth()->user();
    $initials = collect(explode(' ', (string) $user?->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $moduleIcons = ['lms' => 'academic-cap', 'cbt' => 'clipboard-check', 'library' => 'book-open'];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ $tenant->name }}</title>
    @include('partials.assets')
</head>
<body class="flex min-h-full flex-col font-sans text-slate-900 antialiased">
    @if (session('impersonator_id'))
        <div class="flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white">
            <span>You're viewing {{ $tenant->name }} as {{ $user->name }}.</span>
            <form method="POST" action="{{ route('tenant.impersonate.stop') }}">
                @csrf
                <button type="submit" class="underline hover:no-underline">Return to admin</button>
            </form>
        </div>
    @endif

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:gap-6 lg:px-8">
            <a href="{{ route('tenant.home') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-display text-base font-bold text-white shadow-sm">
                    {{ mb_strtoupper(mb_substr($tenant->name, 0, 1)) }}
                </span>
                <span class="hidden max-w-[12rem] truncate text-[15px] font-bold tracking-tight text-slate-900 md:inline">{{ $tenant->name }}</span>
            </a>

            @if ($enabledModules->isNotEmpty())
                <details data-dropdown class="relative hidden lg:block">
                    <summary class="flex cursor-pointer items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Explore
                        <x-icon name="chevron-down" class="details-chevron h-4 w-4 transition" />
                    </summary>
                    <div class="absolute left-0 mt-2 w-80 rounded-2xl bg-white p-2 shadow-xl ring-1 ring-slate-200">
                        @foreach ($enabledModules as $tenantModule)
                            @php($module = $tenantModule->module)
                            <a href="{{ route($module->routeName()) }}" class="flex items-start gap-3 rounded-xl p-3 hover:bg-slate-50">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $module->softClasses() }}">
                                    <x-icon :name="$moduleIcons[$module->value]" class="h-5 w-5" />
                                </span>
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ $module->label() }}</span>
                                    <span class="block text-xs text-slate-500">{{ $module->tagline() }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </details>

                <form method="GET" action="{{ route('tenant.search') }}" class="hidden flex-1 md:block">
                    <label class="relative block max-w-xl">
                        <span class="sr-only">Search</span>
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input type="search" name="q" value="{{ request()->routeIs('tenant.search') ? request('q') : '' }}"
                               placeholder="What do you want to learn?"
                               class="block w-full rounded-full border-0 bg-slate-100 py-2.5 pr-4 pl-11 text-sm text-slate-900 ring-1 ring-transparent ring-inset placeholder:text-slate-500 focus:bg-white focus:ring-2 focus:ring-brand-600">
                    </label>
                </form>
            @endif

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                @if ($enabledModules->isNotEmpty())
                    <a href="{{ route('tenant.search') }}" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Search">
                        <x-icon name="search" />
                    </a>
                @endif

                @auth
                    <a href="{{ route('tenant.home') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 sm:block {{ request()->routeIs('tenant.home') ? 'text-brand-700' : '' }}">
                        My learning
                    </a>

                    @if ($user->isOwner())
                        <x-button :href="route('tenant.manage.dashboard')" variant="dark" size="sm" icon="dashboard" class="hidden sm:inline-flex">
                            Manage
                        </x-button>
                    @endif

                    <details data-dropdown class="relative">
                        <summary class="flex cursor-pointer items-center gap-1 rounded-full p-0.5 hover:ring-4 hover:ring-slate-100">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">{{ $initials ?: '?' }}</span>
                        </summary>
                        <div class="absolute right-0 mt-2 w-64 overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                            <div class="p-1.5 text-sm">
                                <a href="{{ route('tenant.home') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                                    <x-icon name="home" class="h-4 w-4 text-slate-400" /> My learning
                                </a>
                                @if ($hasModule(\App\Enums\Module::Library))
                                    <a href="{{ route('library.checkouts.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                                        <x-icon name="bookmark" class="h-4 w-4 text-slate-400" /> My checkouts
                                    </a>
                                    <a href="{{ route('library.favorites.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                                        <x-icon name="heart" class="h-4 w-4 text-slate-400" /> Favorites
                                    </a>
                                @endif
                                @if ($user->isOwner())
                                    <a href="{{ route('tenant.manage.dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                                        <x-icon name="dashboard" class="h-4 w-4 text-slate-400" /> Manage workspace
                                    </a>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('tenant.logout') }}" class="border-t border-slate-100 p-1.5">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                    <x-icon name="logout" class="h-4 w-4 text-slate-400" /> Log out
                                </button>
                            </form>
                        </div>
                    </details>
                @endauth
            </div>
        </div>

        @if ($enabledModules->isNotEmpty())
            <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="scrollbar-none -mb-px flex gap-6 overflow-x-auto">
                    <a href="{{ route('tenant.home') }}"
                       class="flex shrink-0 items-center gap-2 border-b-2 py-3 text-sm font-medium transition {{ request()->routeIs('tenant.home') ? 'border-brand-600 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        <x-icon name="home" class="h-4 w-4" /> Home
                    </a>
                    @foreach ($enabledModules as $tenantModule)
                        @php($module = $tenantModule->module)
                        @php($active = request()->routeIs($module->routeNamePrefix().'*'))
                        <a href="{{ route($module->routeName()) }}"
                           class="flex shrink-0 items-center gap-2 border-b-2 py-3 text-sm font-medium transition {{ $active ? 'border-brand-600 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                            <x-icon :name="$moduleIcons[$module->value]" class="h-4 w-4" />
                            {{ match ($module->value) { 'lms' => 'Courses', 'cbt' => 'Exams', default => 'Library' } }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif
    </header>

    @if (session('status') || session('error'))
        <div class="mx-auto mt-6 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
                    <x-icon name="check-circle" class="h-5 w-5 text-emerald-600" />
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="flex items-center gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-inset ring-red-600/20">
                    <x-icon name="flag" class="h-5 w-5 text-red-600" />
                    {{ session('error') }}
                </div>
            @endif
        </div>
    @endif

    <main class="flex-1 {{ $flush ? '' : 'mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8' }}">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div class="lg:col-span-2">
                <p class="flex items-center gap-2 text-sm font-bold text-slate-900">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-600 font-display text-xs text-white">{{ mb_strtoupper(mb_substr($tenant->name, 0, 1)) }}</span>
                    {{ $tenant->name }}
                </p>
                <p class="mt-3 max-w-sm text-sm text-slate-500">Learn at your own pace, test what you know, and keep your study resources in one place.</p>
            </div>
            @if ($enabledModules->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Explore</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($enabledModules as $tenantModule)
                            <li><a href="{{ route($tenantModule->module->routeName()) }}" class="text-slate-600 hover:text-brand-700">{{ $tenantModule->module->label() }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Account</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('tenant.home') }}" class="text-slate-600 hover:text-brand-700">My learning</a></li>
                    @if ($user?->isOwner())
                        <li><a href="{{ route('tenant.manage.dashboard') }}" class="text-slate-600 hover:text-brand-700">Manage workspace</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-100">
            <p class="mx-auto max-w-7xl px-4 py-5 text-xs text-slate-400 sm:px-6 lg:px-8">&copy; {{ now()->year }} {{ $tenant->name }} &middot; Powered by e-Library</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
