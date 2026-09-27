<x-app-layout title="API keys">
    <x-page-header title="API keys" subtitle="Connect other systems — HR software, reporting tools, automations — to this workspace." />

    @if (session('new_api_key'))
        <div class="mb-6 rounded-xl bg-emerald-50 p-5 ring-1 ring-emerald-200">
            <p class="text-sm font-semibold text-emerald-900">Your new API key — copy it now. It won't be shown again.</p>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input id="new-api-key" type="text" readonly value="{{ session('new_api_key') }}" aria-label="New API key"
                       class="block w-full rounded-lg border-0 bg-white px-3 py-2 font-mono text-sm text-slate-900 ring-1 ring-inset ring-emerald-300">
                <x-button type="button" variant="secondary" icon="check" onclick="navigator.clipboard.writeText(document.getElementById('new-api-key').value); this.textContent = 'Copied';">Copy</x-button>
            </div>
            <p class="mt-2 text-xs text-emerald-800">Keep it secret, like a password. If it leaks, revoke it below and create a new one.</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="space-y-6 lg:col-span-3">
            <x-card :padded="false">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Keys</h2>
                </div>
                @if ($tokens->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-slate-500">No API keys yet.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($tokens as $token)
                            <li class="flex items-center justify-between gap-4 px-6 py-4">
                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-900">
                                        {{ $token->name }}
                                        <x-badge :color="$token->can_write ? 'amber' : 'slate'">{{ $token->can_write ? 'Read & write' : 'Read only' }}</x-badge>
                                        @if ($token->revoked_at)<x-badge color="red">Revoked</x-badge>@endif
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        <span class="font-mono">{{ $token->prefix }}…</span> &middot; created {{ $token->created_at->format('j M Y') }}@if ($token->createdBy) by {{ $token->createdBy->name }}@endif
                                        &middot; {{ $token->last_used_at ? 'last used '.$token->last_used_at->diffForHumans() : 'never used' }}
                                    </p>
                                </div>
                                @unless ($token->revoked_at)
                                    <form method="POST" action="{{ route('tenant.settings.api.destroy', ['token' => $token->id]) }}" onsubmit="return confirm('Revoke this key? Anything using it will stop working immediately.')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="ghost" size="sm" class="text-red-700">Revoke</x-button>
                                    </form>
                                @endunless
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card>
                <h2 class="text-base font-semibold text-slate-900">Using the API</h2>
                <p class="mt-1 text-sm text-slate-600">Send the key in an <code class="font-mono text-xs">Authorization: Bearer …</code> header. Responses are JSON; lists are paginated with <code class="font-mono text-xs">?page=</code> and <code class="font-mono text-xs">?per_page=</code> (up to 100). Each key can make {{ \App\Http\Middleware\AuthenticateApiToken::PER_MINUTE }} requests a minute.</p>
                <pre class="mt-4 overflow-x-auto rounded-lg bg-ink-950 p-4 text-xs leading-relaxed text-slate-200"><code>curl {{ $baseUrl }}/courses \
  -H "Authorization: Bearer elib_…"</code></pre>

                @php($endpoints = [
                    ['GET', '/people', 'Everyone in the workspace. Filter with ?email=.'],
                    ['POST', '/people', 'Add a member: {"name", "email"}. They are emailed an invitation. Needs a read & write key.'],
                    ['GET', '/courses', 'Courses with their lesson counts.'],
                    ['GET', '/courses/{id}/progress', "Each person's status, progress %, completion and due date. Filter with ?status=completed."],
                    ['POST', '/courses/{id}/assignments', 'Assign to people: {"emails": [...], "due_at": "2026-12-31"}. Needs a read & write key.'],
                    ['GET', '/exams', 'Exams with question counts and pass marks.'],
                    ['GET', '/exams/{id}/results', 'Submitted attempts, newest first. Use ?since=2026-09-01 for new ones only.'],
                    ['GET', '/certificates', 'Course and exam certificates issued, with verification links. Supports ?since=.'],
                ])
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="py-2 pr-4">Method</th><th class="py-2 pr-4">Endpoint</th><th class="py-2">What it does</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($endpoints as [$method, $path, $what])
                                <tr>
                                    <td class="py-2 pr-4 font-mono text-xs font-semibold {{ $method === 'GET' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $method }}</td>
                                    <td class="py-2 pr-4 font-mono text-xs whitespace-nowrap text-slate-800">/api/v1{{ $path }}</td>
                                    <td class="py-2 text-slate-600">{{ $what }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <x-card class="h-fit lg:col-span-2">
            <h2 class="text-base font-semibold text-slate-900">Create a key</h2>
            <form method="POST" action="{{ route('tenant.settings.api.store') }}" class="mt-4 space-y-4">
                @csrf
                <x-input type="text" name="name" label="Name" value="{{ old('name') }}" required maxlength="100" placeholder="e.g. HR system sync" />
                <fieldset>
                    <legend class="mb-1.5 text-sm font-medium text-slate-700">Access</legend>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="radio" name="access" value="read" class="mt-0.5 text-brand-600 focus:ring-brand-600" @checked(old('access', 'read') === 'read')>
                            <span><span class="font-medium">Read only</span><span class="block text-xs text-slate-500">Reports and exports: people, progress, results, certificates.</span></span>
                        </label>
                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="radio" name="access" value="write" class="mt-0.5 text-brand-600 focus:ring-brand-600" @checked(old('access') === 'write')>
                            <span><span class="font-medium">Read &amp; write</span><span class="block text-xs text-slate-500">Also adds people and assigns courses (people are emailed).</span></span>
                        </label>
                    </div>
                </fieldset>
                <x-button type="submit" class="w-full" icon="plus">Create key</x-button>
            </form>
        </x-card>
    </div>
</x-app-layout>
