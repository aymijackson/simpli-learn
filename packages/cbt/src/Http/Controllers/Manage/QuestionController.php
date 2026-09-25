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

        return redirect()
            ->route('cbt.manage.questions.edit', [$exam, $question])
            ->with('status', 'Question added — now add at least two answer options.');
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
        ]);

        $validated['points'] = $validated['points'] ?? 1;
        $validated['answer_type'] = $validated['answer_type'] ?? AnswerType::Single->value;
        $validated['scoring_method'] = $validated['answer_type'] === AnswerType::Single->value
            ? ScoringMethod::AllOrNothing->value
            : ($validated['scoring_method'] ?? ScoringMethod::AllOrNothing->value);

        return $validated;
    }
}
