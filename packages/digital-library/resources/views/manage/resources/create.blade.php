<x-app-layout title="New Resource">
    <a href="{{ route('library.manage.resources.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to resources
    </a>

    <x-page-header title="New resource" />

    <x-card>
        <form method="POST" action="{{ route('library.manage.resources.store') }}" class="space-y-5">
            @csrf
            @include('library::manage.resources._form')
            <x-button type="submit">Create resource</x-button>
        </form>
    </x-card>
</x-app-layout>
