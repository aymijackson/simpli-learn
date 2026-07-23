<x-app-layout title="Manage Resources">
    <x-page-header title="Manage resources" subtitle="Create, edit, and publish your organization's library catalog.">
        <x-slot:actions>
            <x-button :href="route('library.manage.resources.create')">New resource</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($resources->isEmpty())
        <x-empty-state title="No resources yet" description="Add your first resource to get started." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($resources as $resource)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $resource->title }}</p>
                            <p class="text-sm text-slate-500">{{ $resource->category }}{{ $resource->author ? ' · '.$resource->author : '' }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="$resource->is_published ? 'green' : 'slate'">{{ $resource->is_published ? 'Published' : 'Draft' }}</x-badge>
                            <a href="{{ route('library.manage.resources.edit', $resource) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit</a>
                            <form method="POST" action="{{ route('library.manage.resources.destroy', $resource) }}" onsubmit="return confirm('Delete this resource?')">
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
