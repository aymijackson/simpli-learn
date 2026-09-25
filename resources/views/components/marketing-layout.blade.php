@props(['title' => null])

<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}e-Library</title>
    @include('partials.assets')
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    <nav class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-sm font-bold text-slate-900">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-sm text-white">e</span>
                e-Library
            </a>
            <div class="flex items-center gap-3">
                <x-button :href="route('login')" variant="secondary">Log in</x-button>
                <x-button :href="route('signup')">Sign up</x-button>
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

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-slate-200 py-10">
        <div class="mx-auto max-w-6xl px-4 text-sm text-slate-500 sm:px-6 lg:px-8">
            &copy; {{ now()->year }} e-Library. All rights reserved.
        </div>
    </footer>
</body>
</html>
