<x-admin-layout title="Pages">
    <x-page-header title="Pages" subtitle="Content shown on the public marketing site.">
        <x-slot:actions>
            <x-button :href="route('admin.pages.create')">New page</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($pages->isEmpty())
        <x-empty-state title="No pages yet" description='Create a page with the slug "home" to power the public homepage.' />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($pages as $page)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $page->title }}</p>
                            <p class="text-sm text-slate-500">/{{ $page->slug === 'home' ? '' : 'pages/'.$page->slug }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="$page->is_published ? 'green' : 'slate'">{{ $page->is_published ? 'Published' : 'Draft' }}</x-badge>
                            <a href="{{ route('admin.pages.edit', $page) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit</a>
                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?')">
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
</x-admin-layout>
