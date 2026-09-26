<x-app-layout title="Add Team Member">
    <a href="{{ route('tenant.team.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to team
    </a>

    <x-page-header title="Add team member" />

    <x-card>
        <form method="POST" action="{{ route('tenant.team.store') }}" class="space-y-5">
            @csrf

            <x-input type="text" name="name" label="Name" value="{{ old('name') }}" required autofocus />
            <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required />

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Role</label>
                <select name="role" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-xl bg-brand-50 p-4 text-sm text-brand-900 ring-1 ring-brand-100">
                <p class="font-medium">We'll email them an invitation to set their own password.</p>
                <p class="mt-1 text-brand-800">The link works for 7 days. You never need to know or share their password.</p>
            </div>

            <details class="rounded-lg ring-1 ring-slate-200" @if ($errors->has('password')) open @endif>
                <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-slate-700">Or set a password for them instead</summary>
                <div class="space-y-4 border-t border-slate-100 p-4">
                    <x-input type="password" name="password" label="Password" autocomplete="new-password" />
                    <x-input type="password" name="password_confirmation" label="Confirm password" autocomplete="new-password" />
                    <p class="text-xs text-slate-500">They'll get a welcome email with the login link, but not the password — you'll need to give it to them yourself.</p>
                </div>
            </details>

            <x-button type="submit">Add and send invitation</x-button>
        </form>
    </x-card>

    <p class="mt-6 text-sm text-slate-500">
        Adding lots of people? <a href="{{ route('tenant.team.import.create') }}" class="font-medium text-brand-700 hover:text-brand-600">Import them from a CSV file</a>.
    </p>
</x-app-layout>
