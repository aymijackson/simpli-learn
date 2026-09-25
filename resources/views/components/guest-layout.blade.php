@props(['title' => 'Log in', 'maxWidth' => 'max-w-sm'])

@php
    // On tenant auth pages (/t/{tenant}/login etc.) brand the page as that organization.
    $authTenant = app(\App\Support\Tenancy\Tenancy::class)->current();
    $brandName = $authTenant?->name ?? 'e-Library';
    $brandInitial = mb_strtoupper(mb_substr($brandName, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ $brandName }}</title>
    @include('partials.assets')
</head>
<body class="min-h-full font-sans text-slate-900 antialiased">
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,34rem)] xl:grid-cols-[minmax(0,1fr)_minmax(0,40rem)]">
        {{-- Form column --}}
        <div class="flex flex-col px-4 py-8 sm:px-8">
            <a href="{{ $authTenant ? route('tenant.login', $authTenant->slug) : url('/') }}" class="flex items-center gap-2.5 self-start">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-display text-lg font-bold text-white shadow-sm">{{ $brandInitial }}</span>
                <span class="text-lg font-bold tracking-tight text-slate-900">{{ $brandName }}</span>
            </a>

            <div class="flex flex-1 items-center justify-center py-10">
                <div class="w-full {{ $maxWidth }}">
                    {{ $slot }}
                </div>
            </div>

            <p class="text-center text-xs text-slate-400">
                @if ($authTenant)
                    Powered by <a href="{{ route('home') }}" class="font-medium hover:text-slate-600">e-Library</a>
                @else
                    &copy; {{ now()->year }} e-Library
                @endif
            </p>
        </div>

        {{-- Brand panel --}}
        <aside class="relative isolate hidden overflow-hidden bg-ink-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="bg-dots absolute inset-0 -z-10"></div>
            <div class="absolute -top-32 -right-32 -z-10 h-96 w-96 rounded-full bg-brand-600/30 blur-3xl"></div>
            <div class="absolute bottom-0 -left-24 -z-10 h-80 w-80 rounded-full bg-sky-500/20 blur-3xl"></div>

            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-200 ring-1 ring-white/15">
                <x-icon name="sparkles" class="h-3.5 w-3.5" /> {{ $authTenant ? 'Your learning space' : 'Learning · Testing · Library' }}
            </span>

            <div>
                <h2 class="font-display text-4xl leading-tight font-bold text-white">
                    @if ($authTenant)
                        Welcome to {{ $authTenant->name }}
                    @else
                        Learn anywhere. Test fairly. Read freely.
                    @endif
                </h2>
                <ul class="mt-8 space-y-4 text-slate-300">
                    <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300"><x-icon name="academic-cap" /></span> Courses you can take at your own pace</li>
                    <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300"><x-icon name="clipboard-check" /></span> Timed exams with instant results</li>
                    <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300"><x-icon name="book-open" /></span> A digital library that's always open</li>
                </ul>
            </div>

            <p class="text-sm text-slate-500">Secure sign-in &middot; Your progress is saved automatically</p>
        </aside>
    </div>

    @stack('scripts')
</body>
</html>
