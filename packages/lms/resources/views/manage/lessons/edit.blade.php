<x-app-layout :title="$lesson->title">
    <a href="{{ route('lms.manage.courses.edit', $course) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $course->title }}
    </a>

    <x-page-header title="Edit lesson" />

    <x-card>
        <form method="POST" action="{{ route('lms.manage.lessons.update', [$course, $lesson]) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('lms::manage.lessons._form')
            <x-button type="submit">Save changes</x-button>
        </form>
    </x-card>
</x-app-layout>
