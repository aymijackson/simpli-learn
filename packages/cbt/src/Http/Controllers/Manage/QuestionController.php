<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Enums\AnswerType;
use Elibrary\Cbt\Enums\ScoringMethod;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function create(string $tenant, Exam $exam): View
    {
        return view('cbt::manage.questions.create', [
            'exam' => $exam,
            'nextPosition' => $exam->questions()->count(),
            'sections' => $exam->sections,
        ]);
    }

    public function store(Request $request, string $tenant, Exam $exam): RedirectResponse
    {
        $question = $exam->questions()->create($this->validated($request, $exam));
        $this->syncBuiltInOptions($request, $question);

        return match ($question->answer_type) {
            AnswerType::Single, AnswerType::Multiple => redirect()
                ->route('cbt.manage.questions.edit', [$exam, $question])
                ->with('status', 'Question added — now add at least two answer options.'),
            default => redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Question added.'),
        };
    }

    public function edit(string $tenant, Exam $exam, Question $question): View
    {
        abort_unless($question->exam_id === $exam->id, 404);

        return view('cbt::manage.questions.edit', [
            'exam' => $exam,
            'question' => $question,
            'options' => $question->options,
            'sections' => $exam->sections,
        ]);
    }

    public function update(Request $request, string $tenant, Exam $exam, Question $question): RedirectResponse
    {
        abort_unless($question->exam_id === $exam->id, 404);

        $question->update($this->validated($request, $exam));
        $this->syncBuiltInOptions($request, $question);

        // Switching to Single after multiple options were already marked
        // correct would leave the question in an inconsistent state — keep
        // only the first correct option, matching what the options screen
        // enforces going forward once answer_type is Single.
        if ($question->answer_type === AnswerType::Single) {
            $correctIds = $question->options()->where('is_correct', true)->pluck('id');
            if ($correctIds->count() > 1) {
                $question->options()->whereIn('id', $correctIds->slice(1))->update(['is_correct' => false]);
            }
        }

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Question updated.');
    }

    public function destroy(string $tenant, Exam $exam, Question $question): RedirectResponse
    {
        abort_unless($question->exam_id === $exam->id, 404);

        $question->delete();

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Question deleted.');
    }

    private function validated(Request $request, Exam $exam): array
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'answer_type' => ['nullable', Rule::in(array_column(AnswerType::cases(), 'value'))],
            'scoring_method' => ['nullable', Rule::in(array_column(ScoringMethod::cases(), 'value'))],
            'exam_section_id' => ['nullable', Rule::exists('exam_sections', 'id')->where('exam_id', $exam->id)],
            'position' => ['required', 'integer', 'min:0'],
            'points' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'correct_answer' => ['exclude_unless:answer_type,true_false', 'required', Rule::in(['true', 'false'])],
            'accepted_answers' => ['exclude_unless:answer_type,short_answer', 'required', 'string', 'max:2000'],
            'marking_guide' => ['nullable', 'string', 'max:5000'],
        ], [
            'correct_answer.required' => 'Choose whether the statement is true or false.',
            'accepted_answers.required' => 'Add at least one accepted answer.',
        ]);

        unset($validated['correct_answer'], $validated['accepted_answers']);
        $validated['points'] = $validated['points'] ?? 1;
        if (($validated['answer_type'] ?? null) !== AnswerType::Essay->value) {
            $validated['marking_guide'] = null;
        }
        $validated['answer_type'] = $validated['answer_type'] ?? AnswerType::Single->value;
        $validated['scoring_method'] = $validated['answer_type'] !== AnswerType::Multiple->value
            ? ScoringMethod::AllOrNothing->value
            : ($validated['scoring_method'] ?? ScoringMethod::AllOrNothing->value);

        return $validated;
    }

    /**
     * True/false and short-answer questions keep their answer key in the
     * options table (so grading and reporting stay uniform), but owners set
     * it on the question form instead of the options editor.
     */
    private function syncBuiltInOptions(Request $request, Question $question): void
    {
        if ($question->answer_type === AnswerType::TrueFalse) {
            $correct = $request->input('correct_answer');
            $existing = $question->options()->get();

            if ($existing->pluck('option_text')->all() !== ['True', 'False']) {
                $question->options()->delete();
                $question->options()->create(['option_text' => 'True', 'position' => 0, 'is_correct' => $correct === 'true']);
                $question->options()->create(['option_text' => 'False', 'position' => 1, 'is_correct' => $correct === 'false']);

                return;
            }

            foreach ($existing as $option) {
                $option->update(['is_correct' => strtolower($option->option_text) === $correct]);
            }

            return;
        }

        if ($question->answer_type === AnswerType::ShortAnswer) {
            $answers = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('accepted_answers')))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->unique(fn ($line) => Question::normaliseAnswer($line))
                ->take(50)
                ->values();

            $question->options()->delete();
            foreach ($answers as $position => $text) {
                $question->options()->create(['option_text' => $text, 'position' => $position, 'is_correct' => true]);
            }
        }
    }
}
