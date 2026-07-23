<x-app-layout title="New Exam">
    <a href="{{ route('cbt.manage.exams.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to exams
    </a>

    <x-page-header title="New exam" />

    <x-card>
        <form method="POST" action="{{ route('cbt.manage.exams.store') }}" class="space-y-5">
            @csrf
            @include('cbt::manage.exams._form')
            <x-button type="submit">Create exam</x-button>
        </form>
    </x-card>
</x-app-layout>
