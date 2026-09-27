<x-app-layout :title="$path->title">
    <a href="{{ route('lms.manage.paths.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Learning paths
    </a>

    <x-page-header :title="$path->title" eyebrow="Learning path">
        <x-slot:actions>
            @if ($path->is_published)
                <x-button :href="route('lms.paths.show', $path)" variant="secondary" icon="eye">View as learner</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <x-card>
                <form method="POST" action="{{ route('lms.manage.paths.update', $path) }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    @include('lms::manage.paths._form')
                    <x-button type="submit">Save changes</x-button>
                </form>
            </x-card>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <h2 class="text-base font-semibold text-slate-900">Assign this path</h2>
                <p class="mt-1 text-sm text-slate-500">Enrols people in every course and sends one email with the whole path.</p>

                @if ($path->courses->isEmpty())
                    <p class="mt-4 text-sm text-amber-700">Add courses to the path first.</p>
                @else
                    <form method="POST" action="{{ route('lms.manage.paths.assign', $path) }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="flex flex-wrap gap-3">
                            <label class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                                <input type="radio" name="who" value="everyone" class="text-brand-600 focus:ring-brand-600" @checked(old('who', 'everyone') === 'everyone')>
                                Everyone ({{ $people->count() }})
                            </label>
                            <label class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                                <input type="radio" name="who" value="selected" class="text-brand-600 focus:ring-brand-600" @checked(old('who') === 'selected')>
                                Selected people
                            </label>
                        </div>
                        <div class="grid max-h-56 gap-1 overflow-y-auto rounded-lg p-2 ring-1 ring-slate-200">
                            @foreach ($people as $person)
                                <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50">
                                    <input type="checkbox" name="user_ids[]" value="{{ $person->id }}" class="rounded text-brand-600 focus:ring-brand-600" @checked(in_array($person->id, old('user_ids', [])))>
                                    <span class="truncate">{{ $person->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('user_ids')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <div>
                            <label for="due_at" class="mb-1.5 block text-sm font-medium text-slate-700">Due date <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="due_at" type="date" name="due_at" value="{{ old('due_at') }}" min="{{ now()->toDateString() }}"
                                   class="block rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                            @error('due_at')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <x-button type="submit" icon="check">Assign and email</x-button>
                    </form>
                @endif
            </x-card>

            <x-card :padded="false">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Progress</h2>
                    <p class="text-xs text-slate-500">Everyone who has started a course in this path.</p>
                </div>
                @if ($progress->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-slate-500">Nobody has started yet.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($progress as $row)
                            <li class="px-6 py-3">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <a href="{{ route('tenant.team.show', $row['user']) }}" class="truncate font-medium text-slate-900 hover:text-brand-700">{{ $row['user']->name }}</a>
                                    <span class="shrink-0 text-xs text-slate-500">{{ $row['passed'] }} / {{ $row['total'] }} courses</span>
                                </div>
                                <x-progress class="mt-2" :value="$row['percent']" :tone="$row['percent'] === 100 ? 'emerald' : 'brand'" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
