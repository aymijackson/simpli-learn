<x-guest-layout title="Forgot password">
    <x-card>
        <h1 class="text-lg font-semibold text-slate-900">Forgot your password?</h1>
        <p class="mt-1 text-sm text-slate-500">Enter your email and we'll send you a link to reset it.</p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ $action }}" class="mt-6 space-y-5">
            @csrf

            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required autofocus />

            <x-button type="submit" class="w-full">Email password reset link</x-button>
        </form>
    </x-card>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ $loginRoute }}" class="font-medium text-brand-600 hover:text-brand-500">Back to log in</a>
    </p>
</x-guest-layout>
