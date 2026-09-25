<x-app-layout :title="$lesson->title">
    <a href="{{ route('lms.manage.courses.edit', $course) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $course->title }}
    </a>

    <x-page-header title="Edit lesson" />

    <x-card class="mb-8">
        <form method="POST" action="{{ route('lms.manage.lessons.update', [$course, $lesson]) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('lms::manage.lessons._form')
            <x-button type="submit">Save changes</x-button>
        </form>
    </x-card>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Attachments</h2>
    </div>

    @if ($lesson->attachments->isEmpty())
        <x-empty-state title="No attachments yet" />
    @else
        <x-card :padded="false" class="mb-4">
            <ul class="divide-y divide-slate-200">
                @foreach ($lesson->attachments as $attachment)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $attachment->title }}</p>
                            <p class="text-xs text-slate-500">{{ $attachment->type->label() }} &middot; {{ $attachment->access_level->label() }} &middot; {{ number_format($attachment->size / 1024, 0) }} KB</p>
                        </div>
                        <form method="POST" action="{{ route('lms.manage.lessons.attachments.destroy', [$course, $lesson, $attachment]) }}" onsubmit="return confirm('Delete this attachment?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <x-card>
        <form method="POST" action="{{ route('lms.manage.lessons.attachments.store', [$course, $lesson]) }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-[2fr_1fr_1fr_2fr_auto] sm:items-end">
            @csrf
            <x-input type="text" name="title" label="Title" required />
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Type</label>
                <select name="type" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                    @foreach (\Elibrary\Lms\Enums\LessonAttachmentType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Access</label>
                <select name="access_level" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                    @foreach (\Elibrary\Lms\Enums\LessonAttachmentAccessLevel::cases() as $level)
                        <option value="{{ $level->value }}" @selected($level->value === 'secure')>{{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>
            <input type="file" name="file" required class="block w-full text-sm text-slate-900 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
            <x-button type="submit" variant="secondary">Upload</x-button>
        </form>
        <p class="mt-2 text-xs text-slate-500">Video: mp4/webm/mov &middot; Audio: mp3/wav/ogg &middot; File: pdf/doc/docx/ppt/pptx/xls/xlsx/zip/txt. Max 500MB.</p>
    </x-card>
</x-app-layout>
