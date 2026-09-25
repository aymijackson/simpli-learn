<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Lms\Enums\LessonAttachmentAccessLevel;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Elibrary\Lms\Models\LessonAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class LessonAttachmentController extends Controller
{
    public function download(Request $request, string $tenant, Course $course, Lesson $lesson, LessonAttachment $attachment): RedirectResponse
    {
        $this->authorizeAccess($request, $course, $lesson, $attachment);
        abort_unless($attachment->access_level === LessonAttachmentAccessLevel::Open, 404);

        return redirect(Storage::disk('public')->url($attachment->disk_path));
    }

    /**
     * Re-checks the exact same access rule as the lesson page itself on
     * every request (enrollment or preview) — no signed/expiring URL layer
     * on top, since the route is already fully gated here. Built as a real
     * BinaryFileResponse (not Storage::response(), which is a plain
     * fpassthru() with no Range support at all — verified live against a
     * real file: it never sends Accept-Ranges and ignores Range headers
     * entirely) so audio/video seeking actually works; BinaryFileResponse's
     * prepare() handles Range/206-partial-content automatically.
     */
    public function stream(Request $request, string $tenant, Course $course, Lesson $lesson, LessonAttachment $attachment): BinaryFileResponse
    {
        $this->authorizeAccess($request, $course, $lesson, $attachment);
        abort_unless($attachment->access_level === LessonAttachmentAccessLevel::Secure, 404);

        $response = new BinaryFileResponse(Storage::disk('local')->path($attachment->disk_path));
        $response->headers->set('Content-Type', $attachment->mime_type ?? 'application/octet-stream');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $attachment->original_filename ?? basename($attachment->disk_path));

        return $response;
    }

    private function authorizeAccess(Request $request, Course $course, Lesson $lesson, LessonAttachment $attachment): void
    {
        abort_unless($lesson->course_id === $course->id && $attachment->lesson_id === $lesson->id, 404);
        abort_unless($lesson->isAccessibleTo($request->user()), 403);
        abort_unless($lesson->isUnlockedFor($request->user()), 403);
    }
}
