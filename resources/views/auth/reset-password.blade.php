<x-guest-layout title="Reset password">
    <x-card>
        <h1 class="text-lg font-semibold text-slate-900">Choose a new password</h1>
        <p class="mt-1 text-sm text-slate-500">Enter your email and a new password below.</p>

        <form method="POST" action="{{ $action }}" class="mt-6 space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <x-input type="email" name="email" label="Email" value="{{ old('email', $email) }}" required autofocus />
            <x-input type="password" name="password" label="New password" required />
            <x-input type="password" name="password_confirmation" label="Confirm new password" required />

            <x-button type="submit" class="w-full">Reset password</x-button>
        </form>
    </x-card>
</x-guest-layout>
