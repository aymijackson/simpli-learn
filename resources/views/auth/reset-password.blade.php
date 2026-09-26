<x-guest-layout :title="$invite ? 'Set your password' : 'Reset password'">
    <div class="mb-8">
        <h1 class="font-display text-3xl font-bold tracking-tight text-slate-900">{{ $invite ? 'Set your password' : 'Choose a new password' }}</h1>
        <p class="mt-2 text-sm text-slate-500">
            {{ $invite ? 'Welcome! Choose a password to finish setting up your account.' : 'Enter your email and a new password below.' }}
        </p>
    </div>

    <x-card class="sm:p-8">
        <form method="POST" action="{{ $action }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            @if ($invite)
                <input type="hidden" name="invite" value="1">
            @endif

            <x-input type="email" name="email" label="Email" value="{{ old('email', $email) }}" required :readonly="$invite" autofocus />
            <x-input type="password" name="password" :label="$invite ? 'Password' : 'New password'" required autocomplete="new-password" />
            <x-input type="password" name="password_confirmation" :label="$invite ? 'Confirm password' : 'Confirm new password'" required autocomplete="new-password" />
            <p class="text-xs text-slate-500">Use a long passphrase you don't use anywhere else — four or more random words work well.</p>

            <x-button type="submit" class="w-full">{{ $invite ? 'Set password' : 'Reset password' }}</x-button>
        </form>
    </x-card>
</x-guest-layout>
