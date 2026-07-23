<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Enums\NavigationMode;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * One-question-per-page delivery for exams with navigation_mode = OneAtATime.
 * Every answer is saved server-side as soon as it's submitted (via
 * Question::saveAnswerFor, the same helper AttemptController::submit uses),
 * which is what makes "no backward navigation" a real, enforced rule rather
 * than a client-side nicety — see redirectFor().
 */
class AttemptQuestionController extends Controller
{
    public function show(Request $request, string $tenant, ExamAttempt $attempt, int $page): View|RedirectResponse
    {
        if ($redirect = $this->guard($request, $attempt)) {
            return $redirect;
        }

        $exam = $attempt->exam;
        abort_unless($exam->navigation_mode === NavigationMode::OneAtATime, 404);

        $questions = $exam->orderedQuestions($attempt);

        if ($redirect = $this->redirectFor($attempt, $exam, $page, $questions)) {
            return $redirect;
        }

        $attempt->load('answers.selectedOptions');
        $question = $questions[$page - 1];
        $existingAnswer = $attempt->answers->firstWhere('question_id', $question->id);

        return view('cbt::attempts.question', [
            'attempt' => $attempt,
            'exam' => $exam,
            'question' => $question,
            'page' => $page,
            'totalPages' => $questions->count(),
            'selectedOptionIds' => $existingAnswer?->selectedOptions->pluck('id') ?? Collection::make(),
            'isFlagged' => (bool) $existingAnswer?->is_flagged,
        ]);
    }

    public function answer(Request $request, string $tenant, ExamAttempt $attempt, int $page): RedirectResponse
    {
        if ($redirect = $this->guard($request, $attempt)) {
            return $redirect;
        }

        $exam = $attempt->exam;
        abort_unless($exam->navigation_mode === NavigationMode::OneAtATime, 404);

        $questions = $exam->orderedQuestions($attempt);

        if ($redirect = $this->redirectFor($attempt, $exam, $page, $questions)) {
            return $redirect;
        }

        $question = $questions[$page - 1];
        $question->saveAnswerFor(
            $attempt,
            Collection::make($request->input('answers', [])),
            $request->boolean('flagged'),
        );

        $nextPage = $page + 1;

        if ($nextPage > $questions->count()) {
            return redirect()->route('cbt.attempts.review', $attempt);
        }

        return redirect()->route('cbt.attempts.questions.show', [$attempt, $nextPage]);
    }

    public function review(Request $request, string $tenant, ExamAttempt $attempt): View|RedirectResponse
    {
        if ($redirect = $this->guard($request, $attempt)) {
            return $redirect;
        }

        $exam = $attempt->exam;
        abort_unless($exam->navigation_mode === NavigationMode::OneAtATime, 404);

        $questions = $exam->orderedQuestions($attempt);
        $attempt->load('answers.selectedOptions');

        $rows = $questions->values()->map(function ($question, $index) use ($attempt) {
            $answer = $attempt->answers->firstWhere('question_id', $question->id);

            return [
                'page' => $index + 1,
                'question' => $question,
                'answered' => (bool) ($answer && $answer->selectedOptions->isNotEmpty()),
                'flagged' => (bool) $answer?->is_flagged,
            ];
        });

        return view('cbt::attempts.review', [
            'attempt' => $attempt,
            'exam' => $exam,
            'rows' => $rows,
        ]);
    }

    private function guard(Request $request, ExamAttempt $attempt): ?RedirectResponse
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

        return null;
    }

    /**
     * Where does this request actually belong? Everything answered ->
     * review page. Requested page out of range, or (when backward
     * navigation is off) not exactly the next unanswered page -> the next
     * unanswered page. Otherwise null, meaning show the requested page as-is.
     */
    private function redirectFor(ExamAttempt $attempt, Exam $exam, int $requestedPage, Collection $questions): ?RedirectResponse
    {
        $total = $questions->count();
        $nextPage = $attempt->nextPageNumber();

        if ($nextPage > $total) {
            return redirect()->route('cbt.attempts.review', $attempt);
        }

        if ($requestedPage < 1 || $requestedPage > $total) {
            return redirect()->route('cbt.attempts.questions.show', [$attempt, $nextPage]);
        }

        if (! $exam->allow_backward_navigation && $requestedPage !== $nextPage) {
            return redirect()->route('cbt.attempts.questions.show', [$attempt, $nextPage]);
        }

        return null;
    }
}
