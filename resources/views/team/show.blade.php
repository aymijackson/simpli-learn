@php
    $initials = collect(explode(' ', (string) $member->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $has = fn (string $value) => $enabled->contains(fn ($module) => $module->value === $value);
    $completed = $courses->where('progress', 100)->count();
    $passed = $attempts->filter->passed()->count();
    $onLoan = $loans->whereNull('returned_at');
    $isSelf = $member->id === auth()->id();
@endphp

<x-app-layout :title="$member->name">
    <a href="{{ route('tenant.team.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> Team &amp; learners
    </a>

    {{-- Header --}}
    <x-card class="mb-6">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xl font-bold text-brand-700">{{ $initials ?: '?' }}</span>
                <div class="min-w-0">
                    <h1 class="flex flex-wrap items-center gap-2 text-2xl font-bold tracking-tight text-slate-900">
                        {{ $member->name }}
                        <x-badge :color="$member->isOwner() ? 'indigo' : 'slate'">{{ $member->role->label() }}</x-badge>
                    </h1>
                    <p class="mt-0.5 text-sm text-slate-500"><a href="mailto:{{ $member->email }}" class="hover:text-brand-700">{{ $member->email }}</a></p>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1"><x-icon name="calendar" class="h-3.5 w-3.5" /> Joined {{ $member->created_at?->format('M j, Y') }}</span>
                        <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ $lastSignIn ? 'Last signed in '.\Illuminate\Support\Carbon::parse($lastSignIn)->diffForHumans() : 'No sign-in recorded yet' }}</span>
                        @if ($member->hasTwoFactorEnabled())
                            <span class="inline-flex items-center gap-1 font-medium text-emerald-700"><x-icon name="shield-check" class="h-3.5 w-3.5" /> Two-step login on</span>
                        @else
                            <span class="inline-flex items-center gap-1"><x-icon name="shield-check" class="h-3.5 w-3.5" /> Two-step login off</span>
                        @endif
                    </div>
                </div>
            </div>

            @unless ($isSelf)
                <form method="POST" action="{{ route('tenant.team.update', $member) }}" class="flex items-center gap-2">
                    @csrf
                    @method('PUT')
                    <label for="role" class="text-sm text-slate-500">Role</label>
                    <select id="role" name="role" onchange="this.form.requestSubmit()" class="rounded-lg border-0 py-1.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                        @foreach (\App\Enums\UserRole::cases() as $role)
                            <option value="{{ $role->value }}" @selected($member->role === $role)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </form>
            @endunless
        </div>
    </x-card>

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-inset ring-red-600/20">{{ $errors->first() }}</div>
    @endif

    {{-- Headline numbers --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @if ($has('lms'))
            <x-stat-card label="Courses enrolled" :value="$courses->count()" icon="academic-cap" tone="indigo" />
            <x-stat-card label="Courses completed" :value="$completed" icon="trophy" tone="emerald" />
        @endif
        @if ($has('cbt'))
            <x-stat-card label="Exams passed" :value="$passed.' / '.$attempts->count()" icon="clipboard-check" tone="sky" hint="Submitted attempts" />
        @endif
        @if ($has('library'))
            <x-stat-card label="Books on loan" :value="$onLoan->count()" icon="book-open" tone="amber" :hint="$onLoan->filter->isOverdue()->count().' overdue'" />
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($has('lms'))
                <x-card :padded="false">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Course progress</h2>
                    </div>
                    @if ($courses->isEmpty())
                        <p class="px-6 py-8 text-center text-sm text-slate-500">Not enrolled in any courses yet.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($courses as $course)
                                <li class="px-6 py-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <a href="{{ route('lms.courses.show', $course) }}" class="min-w-0 truncate text-sm font-medium text-slate-900 hover:text-brand-700">{{ $course->title }}</a>
                                        @if ($course->progress === 100)
                                            <x-badge color="green" icon="check">Completed</x-badge>
                                        @endif
                                    </div>
                                    <x-progress class="mt-2" :value="$course->progress" label />
                                    @if ($course->enrolled_at)
                                        <p class="mt-1 text-xs text-slate-400">Enrolled {{ \Illuminate\Support\Carbon::parse($course->enrolled_at)->format('M j, Y') }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif

            @if ($has('cbt'))
                <x-card :padded="false">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Exam results</h2>
                    </div>
                    @if ($attempts->isEmpty())
                        <p class="px-6 py-8 text-center text-sm text-slate-500">No exams taken yet.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($attempts as $attempt)
                                <li class="flex items-center justify-between gap-4 px-6 py-3.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ $attempt->exam->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $attempt->submitted_at->format('M j, Y g:ia') }}</p>
                                    </div>
                                    <x-badge :color="$attempt->passed() ? 'green' : 'red'">{{ $attempt->score }}% &middot; {{ $attempt->passed() ? 'Passed' : 'Failed' }}</x-badge>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif

            @if ($has('library'))
                <x-card :padded="false">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Library loans</h2>
                    </div>
                    @if ($loans->isEmpty())
                        <p class="px-6 py-8 text-center text-sm text-slate-500">No books borrowed yet.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($loans as $loan)
                                <li class="flex items-center justify-between gap-4 px-6 py-3.5">
                                    <p class="min-w-0 truncate text-sm font-medium text-slate-900">{{ $loan->resource->title }}</p>
                                    @if ($loan->returned_at)
                                        <span class="shrink-0 text-xs text-slate-500">Returned {{ $loan->returned_at->format('M j') }}</span>
                                    @elseif ($loan->isOverdue())
                                        <x-badge color="red">Overdue</x-badge>
                                    @else
                                        <span class="shrink-0 text-xs text-slate-500">Due {{ $loan->due_at?->format('M j') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card :padded="false">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Recent activity</h2>
                    <a href="{{ route('tenant.manage.activity', ['q' => $member->name]) }}" class="text-xs font-medium text-brand-700 hover:text-brand-600">Full log</a>
                </div>
                @if ($activity->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-slate-500">Nothing recorded yet.</p>
                @else
                    <ul class="space-y-1 p-3">
                        @foreach ($activity as $entry)
                            <li class="rounded-lg px-3 py-2">
                                <p class="text-sm text-slate-700">{{ $entry->user_id === $member->id ? $entry->description : $entry->actor_name.': '.$entry->description }}</p>
                                <p class="text-xs text-slate-400">{{ $entry->created_at->diffForHumans() }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            @unless ($isSelf)
                <x-card>
                    <h2 class="text-base font-semibold text-slate-900">Manage</h2>
                    <div class="mt-4 space-y-2">
                        <x-button :href="route('tenant.team.data.export', $member)" variant="secondary" icon="download" class="w-full">Download their data</x-button>
                        @if ($member->hasTwoFactorEnabled())
                            <form method="POST" action="{{ route('tenant.team.two-factor.reset', $member) }}"
                                  onsubmit="return confirm('Reset two-step login for {{ addslashes($member->name) }}? Only do this if you have confirmed who is asking.')">
                                @csrf
                                <x-button type="submit" variant="secondary" icon="shield-check" class="w-full">Reset two-step login</x-button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('tenant.team.data.erase', $member) }}"
                              onsubmit="return confirm('Erase {{ addslashes($member->name) }}\'s personal data?\n\nTheir name and email are replaced and they can no longer sign in. Payment and exam records are kept anonymously. This cannot be undone.')">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                <x-icon name="x-mark" class="h-4 w-4" /> Erase personal data
                            </button>
                        </form>
                    </div>
                </x-card>
            @endunless
        </div>
    </div>
</x-app-layout>
