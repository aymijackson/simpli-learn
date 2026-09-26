<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Notifications\ExamResultReady;
use App\Support\SafeNotifier;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Certificates\CertificateService;
use Elibrary\Cbt\Enums\AnswerType;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Hand-marking of essay answers. Attempts with unmarked essays have a null
 * score and needs_marking = true; saving marks re-grades the attempt, and
 * once everything is marked the learner gets their result by email.
 */
class MarkingController extends Controller
{
    public function index(): View
    {
        return view('cbt::manage.marking.index', [
            'attempts' => ExamAttempt::where('needs_marking', true)
                ->whereNotNull('submitted_at')
                ->with(['exam', 'user'])
                ->oldest('submitted_at')
                ->get()
                ->filter(fn (ExamAttempt $attempt) => $attempt->exam && $attempt->user)
                ->groupBy(fn (ExamAttempt $attempt) => $attempt->exam->title),
        ]);
    }

    public function show(string $tenant, ExamAttempt $attempt): View
    {
        abort_unless($attempt->isSubmitted(), 404);

        return view('cbt::manage.marking.show', [
            'attempt' => $attempt->load(['exam', 'user']),
            'essays' => $this->essays($attempt),
            'queue' => ExamAttempt::where('needs_marking', true)->whereNotNull('submitted_at')->count(),
        ]);
    }

    public function update(Request $request, string $tenant, ExamAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->isSubmitted(), 404);

        $essays = $this->essays($attempt);
        $rules = [];
        foreach ($essays as $row) {
            $rules["points.{$row['answer']->id}"] = ['required', 'numeric', 'min:0', 'max:'.$row['question']->points];
            $rules["feedback.{$row['answer']->id}"] = ['nullable', 'string', 'max:2000'];
        }
        $validated = $request->validate($rules, [
            'points.*.required' => 'Award points for every answer (0 is fine).',
            'points.*.max' => 'That is more than the question is worth.',
        ]);

        $wasAwaiting = $attempt->needs_marking;

        DB::transaction(function () use ($essays, $validated, $attempt, $request) {
            foreach ($essays as $row) {
                $row['answer']->forceFill([
                    'awarded_points' => round((float) $validated['points'][$row['answer']->id], 2),
                    'feedback' => $validated['feedback'][$row['answer']->id] ?? null,
                    'marked_by_user_id' => $request->user()->id,
                    'marked_at' => now(),
                ])->save();
            }

            $attempt->grade();
        });

        $attempt->refresh();
        ActivityLog::record('exams.marked', "Marked {$attempt->user?->name}'s answers on {$attempt->exam->title}".($attempt->score !== null ? " ({$attempt->score}%)" : ''), $attempt);

        if (! $attempt->needs_marking) {
            if ($attempt->passed()) {
                app(CertificateService::class)->issueIfFree($attempt);
            }
            if ($wasAwaiting && $attempt->user) {
                SafeNotifier::send($attempt->user, new ExamResultReady($attempt, app(Tenancy::class)->current()));
            }
        }

        $next = ExamAttempt::where('needs_marking', true)->whereNotNull('submitted_at')->oldest('submitted_at')->first();

        return $next
            ? redirect()->route('cbt.manage.marking.show', $next)->with('status', "Marked — {$attempt->user?->name} scored {$attempt->score}%. Here's the next one.")
            : redirect()->route('cbt.manage.marking.index')->with('status', "Marked — {$attempt->user?->name} scored {$attempt->score}%. Nothing left to mark.");
    }

    /** @return \Illuminate\Support\Collection<int, array{question: \Elibrary\Cbt\Models\Question, answer: \Elibrary\Cbt\Models\ExamAttemptAnswer}> */
    private function essays(ExamAttempt $attempt)
    {
        $answers = $attempt->answers()->get()->keyBy('question_id');

        return $attempt->exam->orderedQuestions($attempt)
            ->filter(fn ($question) => $question->answer_type === AnswerType::Essay)
            ->map(fn ($question) => ['question' => $question, 'answer' => $answers->get($question->id)])
            ->filter(fn ($row) => $row['answer'] && filled($row['answer']->text_response))
            ->values();
    }
}
