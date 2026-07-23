<x-app-layout title="New Question">
    <a href="{{ route('cbt.manage.exams.edit', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $exam->title }}
    </a>

    <x-page-header title="New question" />

    <x-card>
        <form method="POST" action="{{ route('cbt.manage.questions.store', $exam) }}" class="space-y-5">
            @csrf
            @include('cbt::manage.questions._form')
            <x-button type="submit">Add question</x-button>
        </form>
    </x-card>
</x-app-layout>
