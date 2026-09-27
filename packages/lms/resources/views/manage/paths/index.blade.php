<x-app-layout title="Learning paths">
    <x-page-header title="Learning paths" subtitle="Group courses into a sequence learners take as one — onboarding, a certification track, a role's essentials.">
        <x-slot:actions>
            <x-button :href="route('lms.manage.paths.create')" icon="plus">New path</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($paths->isEmpty())
        <x-empty-state icon="map" title="No learning paths yet" description="Create a path to bundle courses in order and assign them in one go." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($paths as $path)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $path->title }}</p>
                            <p class="text-sm text-slate-500">
                                {{ $path->courses_count }} {{ \Illuminate\Support\Str::plural('course', $path->courses_count) }}
                                @if ($path->is_sequential) &middot; in order @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <x-badge :color="$path->is_published ? 'green' : 'slate'">{{ $path->is_published ? 'Published' : 'Draft' }}</x-badge>
                            <a href="{{ route('lms.manage.paths.edit', $path) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit &amp; assign</a>
                            <form method="POST" action="{{ route('lms.manage.paths.destroy', $path) }}" onsubmit="return confirm('Delete this path? Its courses and everyone\'s progress stay as they are.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Delete</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
