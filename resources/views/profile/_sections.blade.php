{{-- Shared body of the profile page for tenant users and central admins. --}}
<div class="grid gap-8 lg:grid-cols-[16rem_1fr]">
    <div>
        <div class="flex items-center gap-3">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-700">
                {{ collect(explode(' ', (string) $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
            </span>
            <div class="min-w-0">
                <p class="truncate font-semibold text-slate-900">{{ $user->name }}</p>
                <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
            </div>
        </div>
        <nav class="mt-6 hidden space-y-1 text-sm lg:block">
            <a href="#details" class="flex items-center gap-2 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-white"><x-icon name="user-circle" class="h-4 w-4 text-slate-400" /> Your details</a>
            <a href="#password" class="flex items-center gap-2 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-white"><x-icon name="lock" class="h-4 w-4 text-slate-400" /> Password</a>
            <a href="#two-factor" class="flex items-center gap-2 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-white"><x-icon name="shield-check" class="h-4 w-4 text-slate-400" /> Two-step login</a>
            <a href="#data" class="flex items-center gap-2 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-white"><x-icon name="document" class="h-4 w-4 text-slate-400" /> Your data</a>
        </nav>
    </div>

    <div class="space-y-8">
        <x-card id="details" class="scroll-mt-24">
            <h2 class="text-base font-semibold text-slate-900">Your details</h2>
            <p class="mt-1 text-sm text-slate-500">Your name appears on certificates and to your organisation's administrators.</p>

            <form method="POST" action="{{ $routes['update'] }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Full name</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                        @error('name', 'details')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                        @error('email', 'details')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <x-button type="submit">Save details</x-button>
            </form>
        </x-card>

        <x-card id="password" class="scroll-mt-24">
            <h2 class="text-base font-semibold text-slate-900">Change password</h2>
            <p class="mt-1 text-sm text-slate-500">Use a long passphrase you don't use anywhere else — four or more random words work well.</p>

            <form method="POST" action="{{ $routes['password'] }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')
                <div class="max-w-md space-y-5">
                    @foreach ([['current_password', 'Current password', 'current-password'], ['password', 'New password', 'new-password'], ['password_confirmation', 'Confirm new password', 'new-password']] as [$field, $label, $autocomplete])
                        <div>
                            <label for="{{ $field }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input id="{{ $field }}" type="password" name="{{ $field }}" required autocomplete="{{ $autocomplete }}"
                                   class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                            @error($field, 'password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <x-button type="submit">Change password</x-button>
            </form>
        </x-card>

        @php($tenantForIssuer = app(\App\Support\Tenancy\Tenancy::class)->current())
        <x-card id="two-factor" class="scroll-mt-24">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Two-step login</h2>
                    <p class="mt-1 text-sm text-slate-500">After your password, you'll also enter a code from an authenticator app on your phone — so a stolen password alone can't get into your account.</p>
                </div>
                @if ($user->hasTwoFactorEnabled())
                    <x-badge color="green" icon="check">On</x-badge>
                @else
                    <x-badge color="slate">Off</x-badge>
                @endif
            </div>

            @if (session('recovery_codes'))
                <div class="mt-6 rounded-xl bg-amber-50 p-5 ring-1 ring-amber-200">
                    <p class="text-sm font-semibold text-amber-900">Save your recovery codes now — they won't be shown again.</p>
                    <p class="mt-1 text-sm text-amber-800">Each code works once, if you ever lose your phone. Store them in your password manager or print them.</p>
                    <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm text-slate-900 sm:grid-cols-4">
                        @foreach (session('recovery_codes') as $code)
                            <li class="rounded-lg bg-white px-3 py-2 text-center ring-1 ring-amber-200">{{ $code }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($user->hasTwoFactorEnabled())
                <p class="mt-5 text-sm text-slate-600">
                    Turned on {{ $user->two_factor_confirmed_at->format('M j, Y') }} &middot;
                    {{ count($user->two_factor_recovery_codes ?? []) }} recovery {{ \Illuminate\Support\Str::plural('code', count($user->two_factor_recovery_codes ?? [])) }} left
                </p>
                <div class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                    <form method="POST" action="{{ $routes['twoFactorRecoveryCodes'] }}" class="space-y-3">
                        @csrf
                        <label class="block text-sm font-medium text-slate-700" for="regen-password">New recovery codes</label>
                        <input id="regen-password" type="password" name="password" required placeholder="Your password" autocomplete="current-password"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                        <x-button type="submit" variant="secondary" size="sm">Generate new codes</x-button>
                    </form>
                    <form method="POST" action="{{ $routes['twoFactorDisable'] }}" class="space-y-3" onsubmit="return confirm('Turn off two-step login? Your account will be protected by your password only.')">
                        @csrf
                        @method('DELETE')
                        <label class="block text-sm font-medium text-slate-700" for="disable-password">Turn off</label>
                        <input id="disable-password" type="password" name="password" required placeholder="Your password" autocomplete="current-password"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                        <x-button type="submit" variant="danger" size="sm">Turn off two-step login</x-button>
                    </form>
                </div>
                @error('password', 'twoFactor')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
            @elseif ($user->two_factor_secret)
                @php($otpauth = \App\Support\TwoFactor::otpauthUrl($user, $user->two_factor_secret, $tenantForIssuer?->name ?? 'e-Library'))
                <div class="mt-6 grid gap-6 border-t border-slate-100 pt-6 md:grid-cols-[auto_1fr]">
                    <div>
                        <div id="two-factor-qr" data-otpauth="{{ $otpauth }}" class="flex h-48 w-48 items-center justify-center rounded-xl bg-white p-2 ring-1 ring-slate-200" role="img" aria-label="QR code for your authenticator app"></div>
                    </div>
                    <div>
                        <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-700">
                            <li>Install an authenticator app, such as Google Authenticator or Microsoft Authenticator.</li>
                            <li>In the app, add an account and scan this QR code.</li>
                            <li>Can't scan it? Enter this key instead:
                                <code class="mt-1 block rounded-lg bg-slate-100 px-3 py-2 font-mono text-sm tracking-wider break-all text-slate-900">{{ trim(chunk_split($user->two_factor_secret, 4, ' ')) }}</code>
                            </li>
                            <li>Enter the 6-digit code the app shows to finish.</li>
                        </ol>
                        <form method="POST" action="{{ $routes['twoFactorConfirm'] }}" class="mt-5 flex flex-wrap items-start gap-3">
                            @csrf
                            <div>
                                <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required placeholder="123 456"
                                       class="block w-40 rounded-lg border-0 px-3 py-2 text-center font-mono text-lg tracking-widest text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                                @error('code', 'twoFactor')<p class="mt-1 max-w-xs text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <x-button type="submit">Turn on</x-button>
                        </form>
                        <form method="POST" action="{{ $routes['twoFactorCancel'] }}" class="mt-3">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-800">Cancel setup</button>
                        </form>
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
                <script>
                    (function () {
                        var box = document.getElementById('two-factor-qr');
                        if (!box || typeof qrcode === 'undefined') { return; }
                        var qr = qrcode(0, 'M');
                        qr.addData(box.dataset.otpauth);
                        qr.make();
                        box.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 0, scalable: true });
                    })();
                </script>
            @else
                <form method="POST" action="{{ $routes['twoFactorEnable'] }}" class="mt-5">
                    @csrf
                    <x-button type="submit" icon="shield-check">Set up two-step login</x-button>
                </form>
            @endif
        </x-card>

        <x-card id="data" class="scroll-mt-24">
            <h2 class="text-base font-semibold text-slate-900">Your data</h2>
            <p class="mt-1 text-sm text-slate-500">
                Download a copy of everything we hold about you — your account, courses, exam results, certificates, library activity and payments — as a JSON file.
            </p>
            <x-button :href="$routes['export']" variant="secondary" icon="download" class="mt-5">Download my data</x-button>
            @if ($user->tenant_id)
                <p class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-500">
                    Want your personal data erased? Ask your organisation's administrator — they can verify the request and erase it from the Team page.
                </p>
            @endif
        </x-card>

    </div>
</div>
