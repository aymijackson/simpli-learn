<x-input type="text" name="title" label="Title" value="{{ old('title', $lesson->title ?? '') }}" required autofocus />

<x-editor name="content" label="Content" :value="old('content', $lesson->content ?? '')" />

<x-input type="number" name="position" label="Position" value="{{ old('position', $lesson->position ?? $nextPosition ?? 0) }}" min="0" required />
<p class="-mt-3 text-xs text-slate-500">Lessons are shown to learners in ascending position order.</p>

@if ($modules->isNotEmpty())
    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Module (optional)</label>
        <select name="course_module_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
            <option value="">None</option>
            @foreach ($modules as $module)
                <option value="{{ $module->id }}" @selected((string) old('course_module_id', $lesson->course_module_id ?? '') === (string) $module->id)>{{ $module->title }}</option>
            @endforeach
        </select>
    </div>
@endif

<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Exam required to unlock the next lesson (optional)</label>
    <select name="exam_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        <option value="">None</option>
        @foreach ($exams as $exam)
            <option value="{{ $exam->id }}" @selected((string) old('exam_id', $lesson->exam_id ?? '') === (string) $exam->id)>{{ $exam->title }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Only enforced when the course's exam requirements are set to "after each lesson".</p>
</div>

<label class="flex items-center gap-2 text-sm text-slate-600">
    <input type="checkbox" name="is_preview" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_preview', $lesson->is_preview ?? false))>
    Free preview (viewable without enrolling, even on a paid course)
</label>
