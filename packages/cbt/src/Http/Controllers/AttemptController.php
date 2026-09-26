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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

            if ($reason = $exam->startBlockReason($request->user())) {
                return redirect()->route('cbt.exams.show', $exam)->with('error', $reason);
            }

            $attempt = $exam->attempts()->create([
                'user_id' => $userId,
                'started_at' => now(),
            ]);

            return redirect()->route('cbt.attempts.take', $attempt);
        });
    }

    public function take(Request $request, string $tenant, ExamAttempt $attempt): View|RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        if ($attempt->isSubmitted()) {
            return redirect()->route('cbt.attempts.result', $attempt);
        }

        if ($attempt->hasExpired()) {
            $attempt->update(['submitted_at' => now(), 'score' => 0]);

            return redirect()
                ->route('cbt.attempts.result', $attempt)
                ->with('status', 'Time expired before you submitted — this attempt was forfeited.');
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
                    ->with('status', 'Time expired before you submitted — this attempt was forfeited.');
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
                    User::where('tenant_id', $tenant->id)->where('role', UserRole::Owner)->active()->get(),
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
