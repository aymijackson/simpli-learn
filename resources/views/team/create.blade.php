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

            <x-input type="password" name="password" label="Password" required />
            <x-input type="password" name="password_confirmation" label="Confirm password" required />

            <x-button type="submit">Add member</x-button>
        </form>
    </x-card>
</x-app-layout>
