<x-guest-layout :title="__('Forgot your workspace?')">
    <x-card>
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Forgot your workspace?') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('Enter your work email and we\'ll email you a link to each workspace you belong to.') }}</p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('workspace.forgot') }}" class="mt-6 space-y-5">
            @csrf

            <x-input type="email" name="email" :label="__('Email')" value="{{ old('email') }}" required autofocus />

            <x-button type="submit" class="w-full">{{ __('Email me my workspaces') }}</x-button>
        </form>
    </x-card>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">{{ __('Back to log in') }}</a>
    </p>
</x-guest-layout>
