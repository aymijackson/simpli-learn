<x-guest-layout>
    <x-card>
        <h1 class="text-lg font-semibold text-slate-900">Log in to your account</h1>
        <p class="mt-1 text-sm text-slate-500">Enter your email and password below.</p>

        <form method="POST" action="{{ $action }}" class="mt-6 space-y-5">
            @csrf

            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required autofocus />
            <x-input type="password" name="password" label="Password" required />

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    Remember me
                </label>

                <a href="{{ $forgotPasswordRoute }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">
                    Forgot password?
                </a>
            </div>

            <x-button type="submit" class="w-full">Log in</x-button>
        </form>
    </x-card>

    @unless (app(\App\Support\Tenancy\Tenancy::class)->current())
        <p class="mt-6 text-center text-sm text-slate-500">
            Don't have a workspace yet?
            <a href="{{ route('signup') }}" class="font-medium text-brand-600 hover:text-brand-500">Sign up</a>
        </p>
    @endunless
</x-guest-layout>
