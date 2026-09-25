@php
    $user = auth()->user();
    $initials = collect(explode(' ', (string) $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}e-Library Central</title>
    @include('partials.assets')
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    {{-- Mobile drawer --}}
    <div id="admin-sidebar" data-drawer class="fixed inset-0 z-50 hidden lg:hidden">
        <div data-drawer-close class="absolute inset-0 bg-slate-950/60"></div>
        <aside class="relative flex h-full w-72 max-w-[85%] flex-col bg-ink-950">
            <button type="button" data-drawer-close class="absolute top-4 right-3 rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white" aria-label="Close menu">
                <x-icon name="x-mark" />
            </button>
            @include('partials.admin-sidebar')
        </aside>
    </div>

    {{-- Desktop sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col bg-ink-950 lg:flex">
        @include('partials.admin-sidebar')
    </aside>

    <div class="flex min-h-full flex-col lg:pl-72">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button type="button" data-drawer-open="admin-sidebar" class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                <x-icon name="menu" />
            </button>
            <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}" class="hidden shrink-0 font-medium text-slate-500 hover:text-slate-800 sm:block">Central</a>
                @if ($title)
                    <x-icon name="chevron-right" class="hidden h-4 w-4 text-slate-300 sm:block" />
                    <span class="truncate font-semibold text-slate-900">{{ $title }}</span>
                @endif
            </nav>
            <div class="ml-auto flex items-center gap-2">
                @if ($pendingTenants > 0)
                    <a href="{{ route('admin.dashboard') }}" class="hidden items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100 sm:inline-flex">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        {{ $pendingTenants }} awaiting approval
                    </a>
                @endif
                <x-button :href="route('home')" variant="secondary" size="sm" icon="globe" class="hidden sm:inline-flex">Website</x-button>
            </div>
        </header>

        @if (session('status'))
            <div class="px-4 pt-6 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
                    <x-icon name="check-circle" class="h-5 w-5 text-emerald-600" />
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>
</html>
