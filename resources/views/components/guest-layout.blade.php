@props(['title' => 'Log in', 'maxWidth' => 'max-w-sm'])

<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @include('partials.assets')
</head>
<body class="flex min-h-full flex-col items-center bg-slate-50 px-4 py-12 font-sans text-slate-900 antialiased">
    <a href="{{ url('/') }}" class="mb-8 flex items-center gap-2 text-lg font-bold text-slate-900">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">e</span>
        e-Library
    </a>
    <div class="w-full {{ $maxWidth }}">
        {{ $slot }}
    </div>

    @stack('scripts')
</body>
</html>
