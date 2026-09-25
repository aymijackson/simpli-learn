@props(['title' => null])

@php
    $navPages = \App\Models\MarketingPage::where('is_published', true)
        ->where('slug', '!=', 'home')
        ->orderBy('title')
        ->get(['slug', 'title']);
    $sections = [
        ['href' => route('home').'#features', 'label' => 'Platform'],
        ['href' => route('home').'#solutions', 'label' => 'Solutions'],
        ['href' => route('home').'#how-it-works', 'label' => 'How it works'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}e-Library</title>
    <meta name="description" content="e-Library brings learning management, computer-based testing and a digital library together for schools, universities and training organizations.">
    @include('partials.assets')
</head>
<body class="flex min-h-full flex-col font-sans text-slate-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-8 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-display text-lg font-bold text-white shadow-sm">e</span>
                <span class="text-lg font-bold tracking-tight text-slate-900">e-Library</span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex">
                @foreach ($sections as $link)
                    <a href="{{ $link['href'] }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">{{ $link['label'] }}</a>
                @endforeach
                @foreach ($navPages->take(3) as $navPage)
                    <a href="{{ route('pages.show', $navPage) }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->is('pages/'.$navPage->slug) ? 'text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">{{ $navPage->title }}</a>
                @endforeach
            </nav>

            <div class="ml-auto hidden items-center gap-2 sm:flex">
                <a href="{{ route('home') }}#find-workspace" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">Find your workspace</a>
                <x-button :href="route('login')" variant="ghost">Log in</x-button>
                <x-button :href="route('signup')" variant="dark">Get started</x-button>
            </div>

            <button type="button" data-drawer-open="marketing-menu" class="ml-auto rounded-lg p-2 text-slate-700 hover:bg-slate-100 sm:ml-0 lg:hidden" aria-label="Open menu">
                <x-icon name="menu" />
            </button>
        </div>
    </header>

    {{-- Mobile menu --}}
    <div id="marketing-menu" data-drawer class="fixed inset-0 z-50 hidden lg:hidden">
        <div data-drawer-close class="absolute inset-0 bg-slate-950/50"></div>
        <div class="absolute inset-x-0 top-0 rounded-b-2xl bg-white p-5 shadow-xl">
            <div class="flex items-center justify-between">
                <span class="text-lg font-bold">e-Library</span>
                <button type="button" data-drawer-close class="rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="Close menu"><x-icon name="x-mark" /></button>
            </div>
            <nav class="mt-4 grid gap-1">
                @foreach ($sections as $link)
                    <a href="{{ $link['href'] }}" data-drawer-close class="rounded-lg px-3 py-2.5 text-base font-medium text-slate-700 hover:bg-slate-50">{{ $link['label'] }}</a>
                @endforeach
                @foreach ($navPages as $navPage)
                    <a href="{{ route('pages.show', $navPage) }}" class="rounded-lg px-3 py-2.5 text-base font-medium text-slate-700 hover:bg-slate-50">{{ $navPage->title }}</a>
                @endforeach
                <a href="{{ route('home') }}#find-workspace" data-drawer-close class="rounded-lg px-3 py-2.5 text-base font-medium text-slate-700 hover:bg-slate-50">Find your workspace</a>
            </nav>
            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                <x-button :href="route('login')" variant="secondary">Log in</x-button>
                <x-button :href="route('signup')" variant="dark">Get started</x-button>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="mx-auto mt-6 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
                <x-icon name="check-circle" class="h-5 w-5 text-emerald-600" />
                {{ session('status') }}
            </div>
        </div>
    @endif

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-ink-950 text-slate-400">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-5 lg:px-8">
            <div class="lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-700 font-display text-lg font-bold text-white">e</span>
                    <span class="text-lg font-bold text-white">e-Library</span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-relaxed">Courses, computer-based exams and a digital library in one place &mdash; for schools, universities, training centres and professional bodies.</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Platform</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('home') }}#lms" class="hover:text-white">Learning management</a></li>
                    <li><a href="{{ route('home') }}#cbt" class="hover:text-white">Computer-based testing</a></li>
                    <li><a href="{{ route('home') }}#library" class="hover:text-white">Digital library</a></li>
                    <li><a href="{{ route('home') }}#features" class="hover:text-white">All features</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Company</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @forelse ($navPages as $navPage)
                        <li><a href="{{ route('pages.show', $navPage) }}" class="hover:text-white">{{ $navPage->title }}</a></li>
                    @empty
                        <li><a href="{{ route('home') }}#how-it-works" class="hover:text-white">How it works</a></li>
                    @endforelse
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Get started</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('signup') }}" class="hover:text-white">Create a workspace</a></li>
                    <li><a href="{{ route('home') }}#find-workspace" class="hover:text-white">Find your workspace</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white">Log in</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/5">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>&copy; {{ now()->year }} e-Library. All rights reserved.</p>
                <p>Learn anywhere. Test fairly. Read freely.</p>
            </div>
        </div>
    </footer>
</body>
</html>
