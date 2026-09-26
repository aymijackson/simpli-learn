<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Enums\AnswerType;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\Question;
use Elibrary\Cbt\Models\QuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuestionOptionController extends Controller
{
    public function store(Request $request, string $tenant, Exam $exam, Question $question): RedirectResponse
    {
        abort_unless($question->exam_id === $exam->id, 404);

        $validated = $request->validate([
            'option_text' => ['required', 'string'],
        ]);

        $question->options()->create([
            'option_text' => $validated['option_text'],
            'is_correct' => false,
            'position' => $question->options()->count(),
        ]);

        return redirect()->route('cbt.manage.questions.edit', [$exam, $question])->with('status', 'Option added.');
    }

    public function update(Request $request, string $tenant, Exam $exam, Question $question, QuestionOption $option): RedirectResponse
    {
        abort_unless($question->exam_id === $exam->id && $option->question_id === $question->id, 404);

        $validated = $request->validate([
            'option_text' => ['required', 'string'],
        ]);

        $validated['is_correct'] = $request->boolean('is_correct');

        if ($validated['is_correct'] && $question->answer_type->isSingleChoice()) {
            $question->options()->where('id', '!=', $option->id)->update(['is_correct' => false]);
        }

        $option->update($validated);

        return redirect()->route('cbt.manage.questions.edit', [$exam, $question])->with('status', 'Option updated.');
    }

    public function destroy(string $tenant, Exam $exam, Question $question, QuestionOption $option): RedirectResponse
    {
        abort_unless($question->exam_id === $exam->id && $option->question_id === $question->id, 404);

        $option->delete();

        return redirect()->route('cbt.manage.questions.edit', [$exam, $question])->with('status', 'Option deleted.');
    }
}
