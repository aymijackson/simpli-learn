<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AttemptNeedsMarking;
use App\Support\Achievements;
use App\Support\SafeNotifier;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Certificates\CertificateService;
use Elibrary\Cbt\Contracts\ExamPlacements;
use Elibrary\Cbt\Enums\NavigationMode;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Models\ExamProctoringSnapshot;
use Elibrary\Cbt\Support\ProctoringImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttemptController extends Controller
{
    public function start(Request $request, string $tenant, Exam $exam): RedirectResponse
    {
        $userId = $request->user()->id;

        // Without this, a learner could bypass the timer entirely by just
        // hitting "start" again for a fresh deadline instead of finishing
        // (or letting expire) their current attempt — the lock makes the
        // check-then-create atomic so two near-simultaneous clicks can't
        // both slip through and create two attempts in progress at once.
        return Cache::lock("cbt-attempt-start:{$exam->id}:{$userId}", 10)->block(5, function () use ($request, $exam, $userId) {
            $existing = $exam->attempts()
                ->where('user_id', $userId)
                ->whereNull('submitted_at')
                ->first();

            if ($existing) {
                if (! $existing->hasExpired()) {
                    return redirect()->route('cbt.attempts.take', $existing);
                }

                $existing->update(['submitted_at' => now(), 'score' => 0]);
            }

            if ($reason = $exam->startBlockReason($request->user(), $request->ip())) {
                return redirect()->route('cbt.exams.show', $exam)->with('error', $reason);
            }

            $idPhoto = $this->validateStartChecks($request, $exam);

            $attempt = $exam->attempts()->create([
                'user_id' => $userId,
                'started_at' => now(),
                'proctoring_consented_at' => $exam->webcam_proctoring ? now() : null,
            ]);

            if ($idPhoto) {
                ExamProctoringSnapshot::record($attempt, $idPhoto['contents'], $idPhoto['extension'], 'id_check');
            }

            return redirect()->route('cbt.attempts.take', $attempt);
        });
    }

    /**
     * The invigilator's code, and for webcam-proctored exams the candidate's
     * consent and ID check photo (a data: URL from the camera check).
     *
     * @return array{contents: string, extension: string}|null the ID photo
     */
    private function validateStartChecks(Request $request, Exam $exam): ?array
    {
        $rules = [];
        if ($exam->requiresInvigilatorCode()) {
            $rules['invigilator_code'] = ['required', 'string', 'max:50'];
        }
        if ($exam->webcam_proctoring) {
            $rules['proctoring_consent'] = ['required', 'accepted'];
            $rules['id_photo'] = ['required', 'string', 'max:3000000'];
        }

        $request->validate($rules, [
            'proctoring_consent.required' => __('Please agree to camera monitoring to start this exam.'),
            'id_photo.required' => __('Please take your ID check photo first.'),
        ]);

        if ($exam->requiresInvigilatorCode() && ! $exam->invigilatorCodeMatches($request->input('invigilator_code'))) {
            throw ValidationException::withMessages(['invigilator_code' => __('That invigilator code is not right.')]);
        }

        if (! $exam->webcam_proctoring) {
            return null;
        }

        $photo = ProctoringImage::fromDataUrl((string) $request->input('id_photo'));
        if (! $photo) {
            throw ValidationException::withMessages(['id_photo' => __('The ID check photo could not be read. Please take it again.')]);
        }

        return $photo;
    }

    public function take(Request $request, string $tenant, ExamAttempt $attempt): View|RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        if ($attempt->isSubmitted()) {
            return redirect()->route('cbt.attempts.result', $attempt);
        }

        if (! $attempt->exam->allowsNetwork($request->ip())) {
            return redirect()->route('cbt.exams.show', $attempt->exam)->with('error', __('This exam can only be taken from an approved location.'));
        }

        if ($attempt->hasExpired()) {
            $attempt->update(['submitted_at' => now(), 'score' => 0]);

            return redirect()
                ->route('cbt.attempts.result', $attempt)
                ->with('status', __('Time expired before you submitted — this attempt was forfeited.'));
        }

        if ($attempt->exam->navigation_mode === NavigationMode::OneAtATime) {
            return redirect()->route('cbt.attempts.questions.show', [$attempt, $attempt->nextPageNumber()]);
        }

        return view('cbt::attempts.take', [
            'attempt' => $attempt,
            'exam' => $attempt->exam,
            'questions' => $attempt->exam->orderedQuestions($attempt),
        ]);
    }

    public function submit(Request $request, string $tenant, ExamAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        $answers = $request->input('answers', []);
        $flagged = $request->input('flagged', []);
        $hasInlineAnswers = $request->has('answers') || $request->has('flagged');

        // A row lock inside a transaction closes the double-submit race: two
        // near-simultaneous submits (double click, retried request) would
        // otherwise both pass the isSubmitted()/hasExpired() checks before
        // either write lands, and both grade the attempt. The second request
        // now blocks until the first commits, then correctly sees it's
        // already submitted and aborts instead of grading it again.
        return DB::transaction(function () use ($attempt, $answers, $flagged, $hasInlineAnswers) {
            $attempt = ExamAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            abort_if($attempt->isSubmitted(), 403);

            if ($attempt->hasExpired()) {
                $attempt->update(['submitted_at' => now(), 'score' => 0]);

                return redirect()
                    ->route('cbt.attempts.result', $attempt)
                    ->with('status', __('Time expired before you submitted — this attempt was forfeited.'));
            }

            $questions = $attempt->exam->orderedQuestions($attempt);

            // Every question's answer arrives in one request (AllAtOnce), so
            // upsert all of them here. OneAtATime has already saved each one
            // incrementally via AttemptQuestionController::answer, and this
            // final submit carries no answer data at all — skipping the
            // upsert in that case avoids wiping out what was already saved.
            if ($hasInlineAnswers) {
                foreach ($questions as $question) {
                    $isFlagged = in_array((string) $question->id, array_map('strval', (array) $flagged), true);
                    $question->saveResponse($attempt, $answers[$question->id] ?? null, $isFlagged);
                }
            }

            $attempt->forceFill(['submitted_at' => now()])->save();
            $attempt->grade();

            if ($attempt->passed()) {
                app(CertificateService::class)->issueIfFree($attempt);
            }

            Achievements::recordActivity($attempt->user);
            Achievements::evaluate($attempt->user);

            if ($attempt->needs_marking) {
                $tenant = app(Tenancy::class)->current();
                SafeNotifier::send(
                    User::where('tenant_id', $tenant->id)->where('role', UserRole::Owner)->active()->get()
                        ->merge(app(ExamPlacements::class)->markersFor($attempt->exam))
                        ->unique('id')
                        ->values(),
                    new AttemptNeedsMarking($attempt, $tenant),
                );
            }

            return redirect()->route('cbt.attempts.result', $attempt);
        });
    }

    public function result(Request $request, string $tenant, ExamAttempt $attempt): View
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_unless($attempt->isSubmitted(), 404);

        $attempt->load('answers.question.options', 'answers.selectedOptions');

        return view('cbt::attempts.result', [
            'attempt' => $attempt,
            'exam' => $attempt->exam,
            'placement' => app(ExamPlacements::class)->contextFor($attempt->exam, $request->user()),
        ]);
    }
}
