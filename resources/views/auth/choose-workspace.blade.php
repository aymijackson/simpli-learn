<x-guest-layout :title="__('Choose your workspace')">
    <div class="mb-8">
        <h1 class="font-display text-3xl font-bold tracking-tight text-slate-900">{{ __('Choose your workspace') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('Your email and password work in more than one workspace. Which one do you want to open?') }}</p>
    </div>

    <x-card class="sm:p-8">
        <form method="POST" action="{{ route('login.workspace') }}" class="space-y-3">
            @csrf
            @foreach ($accounts as $account)
                @php($workspace = $account->tenant)
                <button type="submit" name="account" value="{{ $account->id }}"
                        class="flex w-full items-center gap-4 rounded-xl px-4 py-3 text-left ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-600">
                    @if ($workspace->logoUrl())
                        <img src="{{ $workspace->logoUrl() }}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-contain">
                    @else
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-600 font-display text-lg font-bold text-white">{{ mb_strtoupper(mb_substr($workspace->name, 0, 1)) }}</span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $workspace->name }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ parse_url(url('/'), PHP_URL_HOST) }}/t/{{ $workspace->slug }}</span>
                    </span>
                    <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-slate-400" />
                </button>
            @endforeach
        </form>
    </x-card>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">{{ __('Back to log in') }}</a>
    </p>
</x-guest-layout>
