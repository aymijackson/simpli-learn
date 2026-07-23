<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    <div class="min-h-full">
        @if (session('impersonator_id'))
            <div class="flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white">
                <span>You're managing {{ $tenant->name }} as {{ auth()->user()->name }}.</span>
                <form method="POST" action="{{ route('tenant.impersonate.stop') }}">
                    @csrf
                    <button type="submit" class="underline hover:no-underline">Return to admin</button>
                </form>
            </div>
        @endif

        <nav class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center">
                        <a href="{{ route('tenant.home') }}" class="flex items-center gap-2 text-sm font-bold text-slate-900">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-sm text-white">
                                {{ mb_strtoupper(mb_substr($tenant->name, 0, 1)) }}
                            </span>
                            <span class="hidden sm:inline">{{ $tenant->name }}</span>
                        </a>

                        <div class="ml-4 hidden gap-1 sm:ml-8 sm:flex">
                            @foreach ($enabledModules as $tenantModule)
                                @php($module = $tenantModule->module)
                                <a href="{{ route($module->routeName()) }}"
                                   class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs($module->routeNamePrefix().'*') ? $module->softClasses() : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <x-module-icon :module="$module" class="h-5 w-5" />
                                    {{ $module->shortLabel() }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        @auth
                            @if (auth()->user()->isOwner())
                                <a href="{{ route('tenant.team.index') }}" class="text-sm font-medium {{ request()->routeIs('tenant.team.*') ? 'text-brand-600' : 'text-slate-600 hover:text-slate-900' }}">Team</a>
                            @endif
                            <span class="hidden text-sm text-slate-500 sm:inline">{{ auth()->user()->email }}</span>
                            <form method="POST" action="{{ route('tenant.logout') }}">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">Log out</button>
                            </form>
                        @endauth
                    </div>
                </div>

                <div class="flex gap-1 overflow-x-auto pb-3 sm:hidden">
                    @foreach ($enabledModules as $tenantModule)
                        @php($module = $tenantModule->module)
                        <a href="{{ route($module->routeName()) }}"
                           class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs($module->routeNamePrefix().'*') ? $module->softClasses() : 'text-slate-600' }}">
                            <x-module-icon :module="$module" class="h-4 w-4" />
                            {{ $module->shortLabel() }}
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>

        @if (session('status'))
            <div class="mx-auto mt-6 max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mx-auto mt-6 max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-inset ring-red-600/20">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>
</html>
