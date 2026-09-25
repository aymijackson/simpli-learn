<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Lms\Enums\LessonAttachmentAccessLevel;
use Elibrary\Lms\Enums\LessonAttachmentType;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Elibrary\Lms\Models\LessonAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LessonAttachmentController extends Controller
{
    public function store(Request $request, string $tenant, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_column(LessonAttachmentType::cases(), 'value'))],
            'access_level' => ['required', Rule::in(array_column(LessonAttachmentAccessLevel::cases(), 'value'))],
            'file' => ['required', 'file', 'max:512000'],
        ]);

        $type = LessonAttachmentType::from($validated['type']);
        $accessLevel = LessonAttachmentAccessLevel::from($validated['access_level']);
        $file = $request->file('file');

        abort_unless(
            in_array(strtolower($file->getClientOriginalExtension()), $type->allowedExtensions(), true),
            422,
            "That file type isn't allowed for {$type->label()} attachments."
        );

        $disk = $accessLevel === LessonAttachmentAccessLevel::Open ? 'public' : 'local';
        $directory = ($accessLevel === LessonAttachmentAccessLevel::Open ? 'lesson-attachments' : 'secure-lesson-attachments').'/'.$lesson->tenant_id;
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, $disk);

        $lesson->attachments()->create([
            'title' => $validated['title'],
            'type' => $type->value,
            'access_level' => $accessLevel->value,
            'disk_path' => $path,
            'mime_type' => $file->getMimeType(),
            'original_filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'position' => $lesson->attachments()->count(),
        ]);

        return redirect()->route('lms.manage.lessons.edit', [$course, $lesson])->with('status', 'Attachment added.');
    }

    public function destroy(string $tenant, Course $course, Lesson $lesson, LessonAttachment $attachment): RedirectResponse
    {
        abort_unless($lesson->course_id === $course->id && $attachment->lesson_id === $lesson->id, 404);

        Storage::disk($attachment->disk())->delete($attachment->disk_path);
        $attachment->delete();

        return redirect()->route('lms.manage.lessons.edit', [$course, $lesson])->with('status', 'Attachment deleted.');
    }
}
