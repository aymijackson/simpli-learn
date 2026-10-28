<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Enums\IntegrityEventType;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Models\ExamProctoringSnapshot;
use Elibrary\Cbt\Support\ProctoringImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Webcam photos sent by the exam page while a proctored attempt is in progress. */
class ProctoringSnapshotController extends Controller
{
    /** Far more than any real attempt takes; stops a script filling the disk. */
    private const MAX_PER_ATTEMPT = 600;

    public function store(Request $request, string $tenant, ExamAttempt $attempt): Response
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_unless($attempt->exam->webcam_proctoring, 404);
        abort_if($attempt->isSubmitted(), 404);

        $validated = $request->validate([
            'image' => ['required', 'file', 'max:'.intdiv(ProctoringImage::MAX_BYTES, 1024)],
            'kind' => ['required', Rule::in(['interval', 'event'])],
            'event_type' => ['nullable', Rule::in(array_column(IntegrityEventType::cases(), 'value'))],
        ]);

        $photo = ProctoringImage::fromUpload($request->file('image'));
        abort_unless($photo, 422);

        if ($attempt->proctoringSnapshots()->withoutGlobalScopes()->count() >= self::MAX_PER_ATTEMPT) {
            return response()->noContent();
        }

        ExamProctoringSnapshot::record(
            $attempt,
            $photo['contents'],
            $photo['extension'],
            $validated['kind'],
            $validated['kind'] === 'event' ? ($validated['event_type'] ?? null) : null,
        );

        return response()->noContent();
    }
}
