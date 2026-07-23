<x-app-layout title="New Course">
    <a href="{{ route('lms.manage.courses.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to courses
    </a>

    <x-page-header title="New course" />

    <x-card>
        <form method="POST" action="{{ route('lms.manage.courses.store') }}" class="space-y-5">
            @csrf
            @include('lms::manage.courses._form')
            <x-button type="submit">Create course</x-button>
        </form>
    </x-card>
</x-app-layout>
