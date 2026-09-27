<x-app-layout title="New learning path">
    <a href="{{ route('lms.manage.paths.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Learning paths
    </a>

    <x-page-header title="New learning path" />

    <x-card>
        <form method="POST" action="{{ route('lms.manage.paths.store') }}" class="space-y-5">
            @csrf
            @include('lms::manage.paths._form')
            <x-button type="submit">Create path</x-button>
        </form>
    </x-card>
</x-app-layout>
