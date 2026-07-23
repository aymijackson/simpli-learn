<x-app-layout title="Team">
    <x-page-header title="Team" subtitle="Manage who has access to this workspace.">
        <x-slot:actions>
            <x-button :href="route('tenant.team.create')">Add member</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-inset ring-red-600/20">
            {{ $errors->first() }}
        </div>
    @endif

    <x-card :padded="false">
        <ul class="divide-y divide-slate-200">
            @foreach ($members as $member)
                <li class="flex items-center justify-between gap-4 px-6 py-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ $member->name }}
                            @if ($member->id === auth()->id())
                                <span class="text-slate-400">(you)</span>
                            @endif
                        </p>
                        <p class="text-sm text-slate-500">{{ $member->email }}</p>
                    </div>

                    @if ($member->id === auth()->id())
                        <x-badge :color="$member->isOwner() ? 'indigo' : 'slate'">{{ $member->role->label() }}</x-badge>
                    @else
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('tenant.team.update', $member) }}">
                                @csrf
                                @method('PUT')
                                <select name="role" onchange="this.form.requestSubmit()" class="rounded-lg border-0 py-1.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                                    @foreach (\App\Enums\UserRole::cases() as $role)
                                        <option value="{{ $role->value }}" @selected($member->role === $role)>{{ $role->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form method="POST" action="{{ route('tenant.team.destroy', $member) }}" onsubmit="return confirm('Remove this team member?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Remove</button>
                            </form>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-card>
</x-app-layout>
