<x-admin-layout title="Edit Page">
    <a href="{{ route('admin.pages.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to pages
    </a>

    <x-page-header title="Edit page" :subtitle="$page->title" />

    <x-card>
        <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('admin.pages._form')
            <div class="flex items-center gap-4">
                <x-button type="submit">Save changes</x-button>
                @if ($page->is_published)
                    <a href="{{ $page->slug === 'home' ? route('home') : route('pages.show', $page) }}" target="_blank" class="text-sm font-medium text-slate-500 underline hover:text-slate-700">
                        View live &rarr;
                    </a>
                @endif
            </div>
        </form>
    </x-card>
</x-admin-layout>
