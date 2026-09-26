<x-app-layout title="Team">
    <x-page-header title="Team" subtitle="Manage who has access to this workspace.">
        <x-slot:actions>
            <x-button :href="route('tenant.team.import.create')" variant="secondary" icon="download">Import from CSV</x-button>
            <x-button :href="route('tenant.team.create')" icon="plus">Add member</x-button>
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
                            <a href="{{ route('tenant.team.show', $member) }}" class="hover:text-brand-700 hover:underline">{{ $member->name }}</a>
                            @if ($member->id === auth()->id())
                                <span class="text-slate-400">(you)</span>
                            @endif
                        </p>
                        <p class="text-sm text-slate-500">{{ $member->email }}</p>
                        @if ($member->hasTwoFactorEnabled())
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><x-icon name="shield-check" class="h-3.5 w-3.5" /> Two-step login on</p>
                        @endif
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
                            <form method="POST" action="{{ route('tenant.team.destroy', $member) }}" onsubmit="return confirm('Deactivate {{ addslashes($member->name) }}?\n\nThey will be signed out and unable to sign in. Their course, exam and payment records are kept, and you can reactivate them at any time.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Deactivate</button>
                            </form>
                            <details data-dropdown class="relative">
                                <summary class="cursor-pointer rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100" aria-label="More actions for {{ $member->name }}">&middot;&middot;&middot;</summary>
                                <div class="absolute right-0 z-10 mt-2 w-64 rounded-xl bg-white p-1.5 text-sm shadow-xl ring-1 ring-slate-200">
                                    <a href="{{ route('tenant.team.show', $member) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                                        <x-icon name="user-circle" class="h-4 w-4 text-slate-400" /> View profile
                                    </a>
                                    <form method="POST" action="{{ route('tenant.team.invite', $member) }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-slate-700 hover:bg-slate-50">
                                            <x-icon name="arrow-right" class="h-4 w-4 text-slate-400" /> Resend invitation
                                        </button>
                                    </form>
                                    @if ($member->hasTwoFactorEnabled())
                                        <p class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Security</p>
                                        <form method="POST" action="{{ route('tenant.team.two-factor.reset', $member) }}"
                                              onsubmit="return confirm('Reset two-step login for {{ addslashes($member->name) }}? Only do this if you have confirmed who is asking — they will be able to sign in with just their password.')">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-slate-700 hover:bg-slate-50">
                                                <x-icon name="shield-check" class="h-4 w-4 text-slate-400" /> Reset two-step login
                                            </button>
                                        </form>
                                    @endif
                                    <p class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Data requests</p>
                                    <a href="{{ route('tenant.team.data.export', $member) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                                        <x-icon name="download" class="h-4 w-4 text-slate-400" /> Download their data
                                    </a>
                                    <form method="POST" action="{{ route('tenant.team.data.erase', $member) }}"
                                          onsubmit="return confirm('Erase {{ addslashes($member->name) }}\'s personal data?\n\nTheir name and email are replaced, favourites, ratings and reading progress are deleted, and they can no longer sign in. Payment and exam records are kept anonymously. This cannot be undone.')">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50">
                                            <x-icon name="x-mark" class="h-4 w-4" /> Erase personal data
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-card>

    @if ($deactivated->isNotEmpty())
        <h2 class="mt-10 mb-3 text-sm font-semibold text-slate-900">Deactivated <span class="font-normal text-slate-400">&middot; can't sign in, records kept</span></h2>
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($deactivated as $member)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="min-w-0 opacity-70">
                            <a href="{{ route('tenant.team.show', $member) }}" class="text-sm font-semibold text-slate-900 hover:text-brand-700 hover:underline">{{ $member->name }}</a>
                            <p class="text-sm text-slate-500">{{ $member->email }} &middot; deactivated {{ $member->deactivated_at->format('M j, Y') }}</p>
                        </div>
                        <form method="POST" action="{{ route('tenant.team.reactivate', $member) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm">Reactivate</x-button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
