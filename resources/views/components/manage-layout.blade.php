@php
    $user = auth()->user();
    $initials = collect(explode(' ', (string) $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}Manage · {{ $tenant->name }}</title>
    @include('partials.assets')
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    @if (session('impersonator_id'))
        <div class="flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white lg:pl-72">
            <span>You're managing {{ $tenant->name }} as {{ $user->name }}.</span>
            <form method="POST" action="{{ route('tenant.impersonate.stop') }}">
                @csrf
                <button type="submit" class="underline hover:no-underline">Return to admin</button>
            </form>
        </div>
    @endif

    {{-- Mobile drawer --}}
    <div id="manage-sidebar" data-drawer class="fixed inset-0 z-50 hidden lg:hidden">
        <div data-drawer-close class="absolute inset-0 bg-slate-950/60"></div>
        <aside class="relative flex h-full w-72 max-w-[85%] flex-col bg-ink-950">
            <button type="button" data-drawer-close class="absolute top-4 right-3 rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white" aria-label="Close menu">
                <x-icon name="x-mark" />
            </button>
            @include('partials.manage-sidebar')
        </aside>
    </div>

    {{-- Desktop sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col bg-ink-950 lg:flex">
        @include('partials.manage-sidebar')
    </aside>

    <div class="flex min-h-full flex-col lg:pl-72">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button type="button" data-drawer-open="manage-sidebar" class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                <x-icon name="menu" />
            </button>

            <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Breadcrumb">
                <a href="{{ route('tenant.manage.dashboard') }}" class="hidden shrink-0 font-medium text-slate-500 hover:text-slate-800 sm:block">Manage</a>
                @if ($title && ! request()->routeIs('tenant.manage.dashboard'))
                    <x-icon name="chevron-right" class="hidden h-4 w-4 text-slate-300 sm:block" />
                    <span class="truncate font-semibold text-slate-900">{{ $title }}</span>
                @endif
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <x-button :href="route('tenant.home')" variant="secondary" size="sm" icon="eye" class="hidden sm:inline-flex">View site</x-button>
                <x-notification-bell />

                <details data-dropdown class="relative">
                    <summary class="flex cursor-pointer items-center gap-2 rounded-full p-0.5 hover:ring-4 hover:ring-slate-100">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">{{ $initials ?: '?' }}</span>
                    </summary>
                    <div class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                        <div class="p-1.5 text-sm">
                            <a href="{{ route('tenant.profile.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                                <x-icon name="user-circle" class="h-4 w-4 text-slate-400" /> Profile &amp; security
                            </a>
                            <a href="{{ route('tenant.home') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                                <x-icon name="eye" class="h-4 w-4 text-slate-400" /> View learner site
                            </a>
                        </div>
                        <form method="POST" action="{{ route('tenant.logout') }}" class="border-t border-slate-100 p-1.5">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                <x-icon name="logout" class="h-4 w-4 text-slate-400" /> Log out
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        @if (session('status') || session('error'))
            <div class="px-4 pt-6 sm:px-6 lg:px-8">
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

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>
</html>
