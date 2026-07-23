<x-admin-layout title="New Page">
    <a href="{{ route('admin.pages.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to pages
    </a>

    <x-page-header title="New page" subtitle="Create a new marketing page." />

    <x-card>
        <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-5">
            @csrf
            @include('admin.pages._form')
            <x-button type="submit">Create page</x-button>
        </form>
    </x-card>
</x-admin-layout>
