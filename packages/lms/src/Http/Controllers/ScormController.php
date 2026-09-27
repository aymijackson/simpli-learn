<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Elibrary\Lms\Support\LessonCompletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves an unpacked SCORM package to people allowed to see the lesson, and
 * saves what the package reports through the SCORM API (progress, score,
 * completion).
 */
class ScormController extends Controller
{
    /** Content types for the files packages actually contain. */
    private const TYPES = [
        'html' => 'text/html', 'htm' => 'text/html', 'js' => 'text/javascript', 'mjs' => 'text/javascript', 'css' => 'text/css',
        'json' => 'application/json', 'xml' => 'application/xml', 'xsd' => 'application/xml', 'txt' => 'text/plain',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp', 'ico' => 'image/x-icon',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'eot' => 'application/vnd.ms-fontobject',
        'pdf' => 'application/pdf', 'vtt' => 'text/vtt', 'swf' => 'application/x-shockwave-flash',
    ];

    public function file(Request $request, string $tenant, Course $course, Lesson $lesson, string $path): BinaryFileResponse
    {
        $this->authorizeLesson($request, $course, $lesson);

        $root = realpath(Storage::disk('local')->path($lesson->scorm_path));
        $file = $root ? realpath($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path)) : false;
        // Never serve anything outside the package folder.
        abort_unless($file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file), 404);

        $type = self::TYPES[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
        $response = new BinaryFileResponse($file, 200, ['Content-Type' => $type]);

        // Authoring tools rely on inline scripts and eval; this looser policy
        // applies only to package files, which only staff can upload.
        $policy = "default-src 'self' data: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:; style-src 'self' 'unsafe-inline' data:; img-src 'self' data: blob:; media-src 'self' data: blob:; font-src 'self' data:; frame-ancestors 'self'; object-src 'none'";
        $response->headers->set('Content-Security-Policy', $policy);
        $response->headers->set('Content-Security-Policy-Report-Only', $policy);
        $response->headers->set('Cache-Control', 'private, max-age=3600');

        return $response;
    }

    /** The runtime posts the package's data here on commit and on exit. */
    public function commit(Request $request, string $tenant, Course $course, Lesson $lesson): JsonResponse
    {
        $this->authorizeLesson($request, $course, $lesson);
        abort_unless($course->isEnrolled($request->user()), 403);

        $state = $request->validate([
            'state' => ['required', 'array', 'max:200'],
            'state.*' => ['nullable', 'string', 'max:65000'],
        ])['state'];
        // Only SCORM data-model keys, and a sane total size.
        $state = array_filter($state, fn ($value, $key) => is_string($key) && str_starts_with($key, 'cmi.') && strlen($key) <= 255, ARRAY_FILTER_USE_BOTH);
        abort_if(strlen(json_encode($state)) > 256 * 1024, 413, 'Too much data.');

        $completed = self::reportsCompletion($state, $lesson->scorm_version);
        $score = self::score($state, $lesson->scorm_version);

        $progress = $lesson->progress()->firstOrNew(['user_id' => $request->user()->id]);
        $progress->scorm_state = $state;
        if ($score !== null) {
            $progress->scorm_score = $score;
        }
        $progress->save();

        if ($completed && $progress->completed_at === null) {
            LessonCompletion::complete($course, $lesson, $request->user());
        }

        return response()->json(['completed' => $completed || $progress->fresh()->completed_at !== null]);
    }

    public static function reportsCompletion(array $state, ?string $version): bool
    {
        if ($version === '2004') {
            return ($state['cmi.completion_status'] ?? null) === 'completed' || ($state['cmi.success_status'] ?? null) === 'passed';
        }

        return in_array($state['cmi.core.lesson_status'] ?? null, ['completed', 'passed'], true);
    }

    /** Score as a percentage, when the package reports one. */
    public static function score(array $state, ?string $version): ?float
    {
        if ($version === '2004' && is_numeric($state['cmi.score.scaled'] ?? null)) {
            return round(max(0, min(1, (float) $state['cmi.score.scaled'])) * 100, 2);
        }

        $raw = $state[$version === '2004' ? 'cmi.score.raw' : 'cmi.core.score.raw'] ?? null;
        $max = $state[$version === '2004' ? 'cmi.score.max' : 'cmi.core.score.max'] ?? null;
        if (! is_numeric($raw)) {
            return null;
        }
        $max = is_numeric($max) && (float) $max > 0 ? (float) $max : 100;

        return round(max(0, min(100, (float) $raw / $max * 100)), 2);
    }

    private function authorizeLesson(Request $request, Course $course, Lesson $lesson): void
    {
        abort_unless($lesson->course_id === $course->id && $lesson->scorm_path, 404);
        abort_unless($lesson->isAccessibleTo($request->user()), 403);
        abort_unless($lesson->isUnlockedFor($request->user()), 403);
    }
}
