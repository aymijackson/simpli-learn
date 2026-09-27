<x-guest-layout :title="$invite ? __('Set your password') : __('Reset password')">
    <div class="mb-8">
        <h1 class="font-display text-3xl font-bold tracking-tight text-slate-900">{{ $invite ? __('Set your password') : __('Choose a new password') }}</h1>
        <p class="mt-2 text-sm text-slate-500">
            {{ $invite ? __('Welcome! Choose a password to finish setting up your account.') : __('Enter your email and a new password below.') }}
        </p>
    </div>

    <x-card class="sm:p-8">
        <form method="POST" action="{{ $action }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            @if ($invite)
                <input type="hidden" name="invite" value="1">
            @endif

            <x-input type="email" name="email" :label="__('Email')" value="{{ old('email', $email) }}" required :readonly="$invite" autofocus />
            <x-input type="password" name="password" :label="$invite ? __('Password') : __('New password')" required autocomplete="new-password" />
            <x-input type="password" name="password_confirmation" :label="$invite ? __('Confirm password') : __('Confirm new password')" required autocomplete="new-password" />
            <p class="text-xs text-slate-500">{{ __('Use a long passphrase you don\'t use anywhere else — four or more random words work well.') }}</p>

            <x-button type="submit" class="w-full">{{ $invite ? __('Set password') : __('Reset password') }}</x-button>
        </form>
    </x-card>
</x-guest-layout>
