<x-guest-layout title="Two-step verification">
    <div class="mb-8">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600"><x-icon name="shield-check" class="h-6 w-6" /></span>
        <h1 class="mt-4 font-display text-3xl font-bold tracking-tight text-slate-900">Two-step verification</h1>
        <p class="mt-2 text-sm text-slate-500">Open your authenticator app and enter the 6-digit code for this account.</p>
    </div>

    <x-card class="sm:p-8">
        <form method="POST" action="{{ $action }}" class="space-y-5">
            @csrf
            <div>
                <label for="code" class="mb-1.5 block text-sm font-medium text-slate-700">Authentication code</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="7" autofocus
                       placeholder="123 456"
                       class="block w-full rounded-lg border-0 px-3 py-3 text-center font-mono text-2xl tracking-[0.4em] text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <x-button type="submit" class="w-full">Verify and sign in</x-button>
        </form>

        <details class="mt-6 border-t border-slate-100 pt-5" @if ($errors->has('recovery_code')) open @endif>
            <summary class="cursor-pointer text-sm font-medium text-brand-700 hover:text-brand-600">Lost your phone? Use a recovery code</summary>
            <form method="POST" action="{{ $action }}" class="mt-4 space-y-4">
                @csrf
                <input name="recovery_code" autocomplete="off" placeholder="xxxxx-xxxxx"
                       class="block w-full rounded-lg border-0 px-3 py-2 font-mono text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                @error('recovery_code')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <x-button type="submit" variant="secondary" class="w-full">Use recovery code</x-button>
            </form>
            <p class="mt-4 text-xs text-slate-500">No recovery codes either? Ask your workspace owner to reset two-step login for your account.</p>
        </details>
    </x-card>
</x-guest-layout>
