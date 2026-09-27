<x-input type="text" name="title" label="Title" value="{{ old('title', $lesson->title ?? '') }}" required autofocus />

<fieldset class="space-y-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
    <legend class="px-1 text-sm font-semibold text-slate-900">Video <span class="font-normal text-slate-500">(optional)</span></legend>
    <div>
        <x-input type="url" name="video_url" label="Video link" value="{{ old('video_url', $lesson->video_url ?? '') }}"
                 placeholder="https://www.youtube.com/watch?v=… · https://vimeo.com/… · https://iframe.mediadelivery.net/play/…" />
        <p class="mt-1 text-xs text-slate-500">Paste a YouTube, Vimeo or Bunny Stream link — it's shown as a player at the top of the lesson. For paid or private courses, Vimeo (with "only on my site" privacy) or Bunny Stream keeps the video off public pages.</p>
        @if (($lesson ?? null)?->video())
            <p class="mt-1 text-xs font-medium text-emerald-700">Recognised: {{ $lesson->video()['label'] }} video.</p>
        @endif
    </div>
    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" name="require_video_watch" value="1" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('require_video_watch', $lesson->require_video_watch ?? true))>
        <span>
            <span class="font-medium text-slate-700">Learners must watch the video to complete this lesson</span>
            <span class="block text-xs text-slate-500">The lesson completes itself once {{ \Elibrary\Lms\Support\VideoEmbed::WATCHED_TO_COMPLETE }}% has been watched; skipping ahead doesn't count. Untick to let learners mark it complete at any time.</span>
        </span>
    </label>
</fieldset>

<fieldset class="space-y-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
    <legend class="px-1 text-sm font-semibold text-slate-900">SCORM package <span class="font-normal text-slate-500">(optional)</span></legend>
    @if (($lesson ?? null)?->scorm_path)
        <p class="text-sm text-emerald-800">A SCORM {{ $lesson->scorm_version }} package is attached (starts at <span class="font-mono">{{ $lesson->scorm_launch }}</span>). Upload a new zip to replace it.</p>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="remove_scorm" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600"> Remove the package
        </label>
    @endif
    <div>
        <label for="scorm-package" class="mb-1.5 block text-sm font-medium text-slate-700">SCORM zip (1.2 or 2004)</label>
        <input id="scorm-package" type="file" name="scorm_package" accept=".zip,application/zip"
               class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 file:ring-1 file:ring-slate-300 hover:file:bg-slate-50">
        <p class="mt-1 text-xs text-slate-500">Exported from Articulate Storyline/Rise, iSpring, Adobe Captivate and similar tools. It plays inside the lesson and reports progress, score and completion. Only upload packages from sources you trust — they run their own scripts.</p>
        @error('scorm_package')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" name="scorm_required" value="1" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('scorm_required', $lesson->scorm_required ?? true))>
        <span>
            <span class="font-medium text-slate-700">The lesson completes only when the package reports it finished</span>
            <span class="block text-xs text-slate-500">Untick for packages that never report completion, so learners can mark the lesson complete themselves.</span>
        </span>
    </label>
</fieldset>

<x-editor name="content" label="Lesson notes (optional when there's a video or SCORM package)" :value="old('content', $lesson->content ?? '')" />

<x-input type="number" name="position" label="Position" value="{{ old('position', $lesson->position ?? $nextPosition ?? 0) }}" min="0" required />
<p class="-mt-3 text-xs text-slate-500">Lessons are shown to learners in ascending position order.</p>

@if ($modules->isNotEmpty())
    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Module (optional)</label>
        <select aria-label="Module (optional)" name="course_module_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
            <option value="">None</option>
            @foreach ($modules as $module)
                <option value="{{ $module->id }}" @selected((string) old('course_module_id', $lesson->course_module_id ?? '') === (string) $module->id)>{{ $module->title }}</option>
            @endforeach
        </select>
    </div>
@endif

@if (auth()->user()->isOwner())
<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Exam required to unlock the next lesson (optional)</label>
    <select aria-label="Exam required to unlock the next lesson (optional)" name="exam_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        <option value="">None</option>
        @foreach ($exams as $exam)
            <option value="{{ $exam->id }}" @selected((string) old('exam_id', $lesson->exam_id ?? '') === (string) $exam->id)>{{ $exam->title }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Only enforced when the course's exam requirements are set to "after each lesson".</p>
</div>
@elseif (($lesson ?? null)?->exam)
    <p class="text-sm text-slate-500">Exam to unlock the next lesson: {{ $lesson->exam->title }} <span class="text-slate-500">(set by an owner)</span></p>
@endif

<label class="flex items-center gap-2 text-sm text-slate-600">
    <input type="checkbox" name="is_preview" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_preview', $lesson->is_preview ?? false))>
    Free preview (viewable without enrolling, even on a paid course)
</label>
