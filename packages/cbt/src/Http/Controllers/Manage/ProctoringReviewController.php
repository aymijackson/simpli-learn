<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Models\ExamProctoringSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** A reviewer's verdict on a monitored attempt, and its webcam photos. */
class ProctoringReviewController extends Controller
{
    public function update(Request $request, string $tenant, Exam $exam, ExamAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->exam_id === $exam->id, 404);

        $validated = $request->validate([
            'review_status' => ['nullable', Rule::in(array_keys(ExamAttempt::REVIEW_STATUSES))],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $attempt->forceFill([
            'review_status' => $validated['review_status'] ?? null,
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return redirect()->route('cbt.manage.analytics.attempts.integrity', [$exam, $attempt])->with('status', 'Review saved.');
    }

    public function snapshot(string $tenant, Exam $exam, ExamAttempt $attempt, int $snapshot): StreamedResponse
    {
        abort_unless($attempt->exam_id === $exam->id, 404);

        $photo = ExamProctoringSnapshot::withoutGlobalScopes()
            ->where('exam_attempt_id', $attempt->id)
            ->findOrFail($snapshot);

        $disk = Storage::disk(ExamProctoringSnapshot::DISK);
        abort_unless($disk->exists($photo->path), 404);

        return $disk->response($photo->path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
