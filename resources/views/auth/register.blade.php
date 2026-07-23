<x-guest-layout title="Sign up" max-width="max-w-2xl">
    <x-card>
        <h1 class="text-lg font-semibold text-slate-900">Create your workspace</h1>
        <p class="mt-1 text-sm text-slate-500">Your workspace goes live once our team reviews and approves it.</p>

        <form method="POST" action="{{ route('signup') }}" class="mt-6 space-y-6">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input type="text" name="organization_name" id="organization_name" label="Organization name" value="{{ old('organization_name') }}" required autofocus />
                <div>
                    <x-input type="text" name="slug" id="slug" label="Workspace URL" value="{{ old('slug') }}" required />
                    <p class="mt-1 text-xs text-slate-500" id="slug-preview">/t/your-workspace</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input type="text" name="name" label="Your name" value="{{ old('name') }}" required />
                <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input type="password" name="password" label="Password" required />
                <x-input type="password" name="password_confirmation" label="Confirm password" required />
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-slate-700">Choose your packages</p>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach (\App\Enums\Module::cases() as $module)
                        <label class="flex cursor-pointer flex-col gap-2 rounded-xl border border-slate-200 p-4 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                            <div class="flex items-center justify-between">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $module->softClasses() }}">
                                    <x-module-icon :module="$module" class="h-5 w-5" />
                                </div>
                                <input type="checkbox" name="modules[]" value="{{ $module->value }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(in_array($module->value, old('modules', [])))>
                            </div>
                            <span class="text-sm font-semibold text-slate-900">{{ $module->label() }}</span>
                            <span class="text-xs text-slate-500">{{ $module->tagline() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('modules')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-button type="submit" class="w-full">Create workspace</x-button>
        </form>
    </x-card>

    <p class="mt-6 text-center text-sm text-slate-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">Log in</a>
    </p>

    @push('scripts')
        <script>
            (function () {
                const nameField = document.getElementById('organization_name');
                const slugField = document.getElementById('slug');
                const preview = document.getElementById('slug-preview');
                let slugTouched = slugField.value.length > 0;

                const slugify = (value) => value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');

                const updatePreview = () => {
                    preview.textContent = '/t/' + (slugField.value || 'your-workspace');
                };

                nameField.addEventListener('input', () => {
                    if (!slugTouched) {
                        slugField.value = slugify(nameField.value);
                        updatePreview();
                    }
                });

                slugField.addEventListener('input', () => {
                    slugTouched = true;
                    updatePreview();
                });

                updatePreview();
            })();
        </script>
    @endpush
</x-guest-layout>
