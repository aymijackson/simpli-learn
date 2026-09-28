<x-app-layout title="Job titles">
    <a href="{{ route('tenant.team.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> Team
    </a>

    <x-page-header title="Job titles" subtitle="Used to recommend courses and library resources to people. Rename a title to an existing one to merge the two." />

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-inset ring-red-600/20">{{ $errors->first() }}</div>
    @endif

    @if ($titles->isEmpty())
        <x-empty-state icon="users" title="No job titles yet" description="Add one when you add someone to the team, on their page, or in a CSV import." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-100">
                @foreach ($titles as $title)
                    <li class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <form method="POST" action="{{ route('tenant.team.job-titles.update', $title) }}" class="flex flex-1 items-center gap-2">
                            @csrf
                            @method('PUT')
                            <label for="job-title-{{ $title->id }}" class="sr-only">Name</label>
                            <input id="job-title-{{ $title->id }}" name="name" value="{{ $title->name }}" required maxlength="120"
                                   class="block w-full max-w-xs rounded-lg border-0 px-3 py-1.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                            <x-button type="submit" variant="secondary" size="sm">Rename</x-button>
                        </form>
                        <div class="flex items-center gap-4">
                            <p class="text-xs text-slate-500">
                                {{ $title->users_count }} {{ \Illuminate\Support\Str::plural('person', $title->users_count) }}
                                &middot; {{ $title->courses_count }} {{ \Illuminate\Support\Str::plural('course', $title->courses_count) }}
                                &middot; {{ $title->library_resources_count }} library {{ \Illuminate\Support\Str::plural('resource', $title->library_resources_count) }}
                            </p>
                            <form method="POST" action="{{ route('tenant.team.job-titles.destroy', $title) }}"
                                  onsubmit="return confirm(@js('Remove “'.$title->name.'”? People with this title will have none, and its recommendations are removed.'))">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="ghost" size="sm" class="text-red-700">Remove</x-button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
