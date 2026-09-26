<x-app-layout :title="$course->title">
    <a href="{{ route('lms.manage.courses.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to courses
    </a>

    <x-page-header title="Edit course" :subtitle="$course->title" />

    <x-card class="mb-8">
        <form method="POST" action="{{ route('lms.manage.courses.update', $course) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')
            @include('lms::manage.courses._form')
            <div class="flex items-center gap-4">
                <x-button type="submit">Save changes</x-button>
                @if ($course->is_published)
                    <a href="{{ route('lms.courses.show', $course) }}" target="_blank" class="text-sm font-medium text-slate-500 underline hover:text-slate-700">
                        View live &rarr;
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Modules</h2>
    </div>

    @if ($modules->isEmpty())
        <x-empty-state title="No modules yet" description="Add a module below to organize lessons — modules are also used to gate progress when exam requirements are set to 'after each module'." />
    @else
        <div class="mb-4 space-y-3">
            @foreach ($modules as $module)
                <x-card>
                    <form method="POST" action="{{ route('lms.manage.modules.update', [$course, $module]) }}" class="grid gap-3 sm:grid-cols-[2fr_1fr_2fr_auto] sm:items-end">
                        @csrf
                        @method('PUT')
                        <x-input type="text" name="title" label="Title" value="{{ $module->title }}" />
                        <x-input type="number" name="position" label="Position" value="{{ $module->position }}" min="0" />
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Exam to unlock next module</label>
                            <select name="exam_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                                <option value="">None</option>
                                @foreach ($exams as $exam)
                                    <option value="{{ $exam->id }}" @selected($module->exam_id === $exam->id)>{{ $exam->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <x-button type="submit" variant="secondary">Save</x-button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('lms.manage.modules.destroy', [$course, $module]) }}" onsubmit="return confirm('Delete this module? Lessons in it will become unassigned.')" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Delete module</button>
                    </form>
                </x-card>
            @endforeach
        </div>
    @endif

    <x-card class="mb-8">
        <form method="POST" action="{{ route('lms.manage.modules.store', $course) }}" class="grid gap-3 sm:grid-cols-[2fr_1fr_auto] sm:items-end">
            @csrf
            <x-input type="text" name="title" label="New module title" required />
            <x-input type="number" name="position" label="Position" value="{{ $modules->count() }}" min="0" required />
            <x-button type="submit" variant="secondary">Add module</x-button>
        </form>
    </x-card>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Lessons</h2>
        <x-button :href="route('lms.manage.lessons.create', $course)" variant="secondary">Add lesson</x-button>
    </div>

    @if ($lessons->isEmpty())
        <x-empty-state title="No lessons yet" />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($lessons as $lesson)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{{ $loop->iteration }}</span>
                            <div>
                                <span class="text-sm font-medium text-slate-900">{{ $lesson->title }}</span>
                                @if ($lesson->module)
                                    <span class="ml-2 text-xs text-slate-500">{{ $lesson->module->title }}</span>
                                @endif
                                @if ($lesson->exam)
                                    <span class="ml-2 text-xs text-indigo-600">gated by {{ $lesson->exam->title }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('lms.manage.lessons.edit', [$course, $lesson]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit</a>
                            <form method="POST" action="{{ route('lms.manage.lessons.destroy', [$course, $lesson]) }}" onsubmit="return confirm('Delete this lesson?')">
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
</x-app-layout>
