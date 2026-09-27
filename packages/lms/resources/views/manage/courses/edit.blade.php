<x-app-layout :title="$course->title">
    <a href="{{ route('lms.manage.courses.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to courses
    </a>

    <x-page-header title="Edit course" :subtitle="$course->title" />

    @if ($isOwner)
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

        <x-card class="mb-8">
            <h2 class="text-base font-semibold text-slate-900">Instructors</h2>
            <p class="mt-1 text-sm text-slate-500">Instructors can edit this course's lessons and modules, see its report and mark its written exam answers. Pricing, publishing and exams stay with owners. They're shown to learners on the course page.</p>
            <form method="POST" action="{{ route('lms.manage.courses.instructors', $course) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <fieldset>
                    <legend class="sr-only">Choose instructors</legend>
                    <div class="grid max-h-56 gap-1 overflow-y-auto rounded-lg p-2 ring-1 ring-slate-200 sm:grid-cols-2">
                        @foreach ($members as $member)
                            <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50">
                                <input type="checkbox" name="instructor_ids[]" value="{{ $member->id }}" class="rounded text-brand-600 focus:ring-brand-600" @checked($instructorIds->contains($member->id))>
                                <span class="truncate">{{ $member->name }}</span>
                                @if ($member->isOwner())<span class="shrink-0 text-xs text-slate-500">owner</span>@endif
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                @error('instructor_ids')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <x-button type="submit" variant="secondary" icon="check">Save instructors</x-button>
            </form>
        </x-card>
    @else
        <x-card class="mb-8">
            <p class="text-sm text-slate-600">You teach this course: you can edit its modules and lessons below. Its title, pricing, publishing and exams are managed by the workspace owner.</p>
            <div class="mt-3 flex flex-wrap gap-4 text-sm">
                <a href="{{ route('lms.manage.courses.report', $course) }}" class="font-medium text-brand-700 hover:underline">Learner progress</a>
                @if ($course->is_published)<a href="{{ route('lms.courses.show', $course) }}" class="font-medium text-brand-700 hover:underline">View as a learner</a>@endif
            </div>
        </x-card>
    @endif

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
                        @if ($isOwner)
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Exam to unlock next module</label>
                            <select aria-label="Exam to unlock next module" name="exam_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                                <option value="">None</option>
                                @foreach ($exams as $exam)
                                    <option value="{{ $exam->id }}" @selected($module->exam_id === $exam->id)>{{ $exam->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                            <p class="text-sm text-slate-500">Exam: {{ $module->exam?->title ?? 'none' }}</p>
                        @endif
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
