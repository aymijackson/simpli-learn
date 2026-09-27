@php
    $current = old('brand_color', $workspace->brand_color ?? \App\Support\BrandColor::DEFAULT);
    $current = \App\Support\BrandColor::isValidHex($current) ? strtolower($current) : \App\Support\BrandColor::DEFAULT;
@endphp

<x-app-layout title="Settings & branding">
    <x-page-header title="Settings & branding" subtitle="How your workspace looks to everyone in it — on every page, the sign-in screen and the back office." />

    <div class="grid gap-6 lg:grid-cols-5">
        <x-card class="lg:col-span-3">
            <form method="POST" action="{{ route('tenant.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <x-input type="text" name="name" label="Workspace name" value="{{ old('name', $workspace->name) }}" required maxlength="120" />

                <div>
                    <p class="mb-1.5 text-sm font-medium text-slate-700">Logo</p>
                    <div class="flex items-center gap-4">
                        <x-workspace-mark :tenant="$workspace" class="h-16! w-16!" />
                        <div class="min-w-0 flex-1 space-y-2">
                            <input type="file" name="logo" aria-label="Upload a logo" accept="image/png,image/jpeg,image/webp"
                                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                            <p class="text-xs text-slate-500">PNG, JPG or WebP, up to 2 MB. A square or wide logo on a transparent background works best.</p>
                            @if ($workspace->logo_path)
                                <label class="flex items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600"> Remove the logo
                                </label>
                            @endif
                        </div>
                    </div>
                    @error('logo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <fieldset>
                    <legend class="mb-1.5 text-sm font-medium text-slate-700">Brand colour</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($presets as $hex => $label)
                            <label class="cursor-pointer" title="{{ $label }}">
                                <input type="radio" name="brand_preset" value="{{ $hex }}" class="peer sr-only" data-brand-preset @checked(strtolower($current) === $hex)>
                                <span class="block h-9 w-9 rounded-full ring-2 ring-transparent ring-offset-2 peer-checked:ring-slate-900 peer-focus-visible:ring-slate-400" style="background: {{ $hex }}"></span>
                                <span class="sr-only">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-center gap-3">
                        <input type="color" id="brand-color-picker" value="{{ $current }}" aria-label="Pick any colour" class="h-10 w-14 cursor-pointer rounded-lg border-0 bg-transparent p-0">
                        <input type="text" name="brand_color" id="brand-color" aria-label="Brand colour (hex)" value="{{ $current }}" maxlength="7" pattern="#[0-9a-fA-F]{6}"
                               class="block w-32 rounded-lg border-0 px-3 py-2 font-mono text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                        <span class="text-xs text-slate-500">Used for buttons, links and highlights. It must be dark enough for white text.</span>
                    </div>
                    @error('brand_color')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </fieldset>

                <fieldset class="space-y-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                    <legend class="px-1 text-sm font-semibold text-slate-900">Single sign-on</legend>
                    @if ($ssoAvailable)
                        <p class="text-xs text-slate-500">Let people sign in with their work Google or Microsoft account instead of a password. It only works for email addresses that already have an account here, unless you add your email domains below.</p>
                        @foreach ($ssoAvailable as $key => $label)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="sso_providers[]" value="{{ $key }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(in_array($key, old('sso_providers', $workspace->sso_providers ?? []), true))>
                                Show "Continue with {{ $label }}"
                            </label>
                        @endforeach
                        <div>
                            <label for="sso-domains" class="mb-1.5 block text-sm font-medium text-slate-700">Email domains that may join automatically <span class="font-normal text-slate-500">(optional)</span></label>
                            <input id="sso-domains" type="text" name="sso_domains" value="{{ old('sso_domains', implode(', ', $workspace->sso_domains ?? [])) }}" placeholder="e.g. acme.com, acme.co.uk"
                                   class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                            <p class="mt-1 text-xs text-slate-500">Anyone signing in with Google/Microsoft using one of these domains gets a member account on their first sign-in. Leave empty to allow invited people only.</p>
                            @error('sso_domains')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <p class="text-xs text-slate-500">Sign-in with Google or Microsoft isn't set up on this platform yet. Ask the platform administrator.</p>
                    @endif
                </fieldset>

                <x-button type="submit" icon="check">Save settings</x-button>
            </form>
        </x-card>

        <div class="lg:col-span-2">
            <x-card>
                <p class="text-sm font-semibold text-slate-900">Preview</p>
                <div id="brand-preview" class="mt-4 space-y-4 rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200" style="--p: {{ $current }}">
                    <div class="flex items-center gap-2.5">
                        <x-workspace-mark :tenant="$workspace" />
                        <span class="font-bold text-slate-900">{{ $workspace->name }}</span>
                    </div>
                    <span class="inline-flex rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background: var(--p)">Start learning</span>
                    <p class="text-sm">A <span class="font-semibold" style="color: var(--p)">link in your colour</span>, and a progress bar:</p>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full w-2/3 rounded-full" style="background: var(--p)"></div></div>
                </div>
                <p class="mt-3 text-xs text-slate-500">Changes apply to everyone as soon as you save.</p>
            </x-card>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const text = document.getElementById('brand-color');
                const picker = document.getElementById('brand-color-picker');
                const preview = document.getElementById('brand-preview');
                const set = (value) => {
                    if (!/^#[0-9a-f]{6}$/i.test(value)) return;
                    text.value = value.toLowerCase();
                    picker.value = value.toLowerCase();
                    preview.style.setProperty('--p', value);
                    document.querySelectorAll('[data-brand-preset]').forEach((radio) => { radio.checked = radio.value === value.toLowerCase(); });
                };
                picker.addEventListener('input', () => set(picker.value));
                text.addEventListener('input', () => set(text.value));
                document.querySelectorAll('[data-brand-preset]').forEach((radio) => radio.addEventListener('change', () => set(radio.value)));
            })();
        </script>
    @endpush
</x-app-layout>
