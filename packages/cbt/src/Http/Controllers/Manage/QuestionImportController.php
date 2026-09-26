<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Enums\AnswerType;
use Elibrary\Cbt\Enums\ScoringMethod;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionImportController extends Controller
{
    public function create(string $tenant, Exam $exam): View
    {
        return view('cbt::manage.questions.import', ['exam' => $exam]);
    }

    public function template(string $tenant, Exam $exam): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'question_text', 'answer_type', 'scoring_method', 'section', 'points',
                'option_1', 'correct_1', 'option_2', 'correct_2', 'option_3', 'correct_3', 'option_4', 'correct_4',
            ]);

            fputcsv($handle, [
                'What is the capital of France?', 'single', 'all_or_nothing', '', '1',
                'Paris', 'yes', 'London', 'no', 'Berlin', 'no', 'Madrid', 'no',
            ]);

            fputcsv($handle, [
                'Which of the following are prime numbers?', 'multiple', 'partial_credit', '', '2',
                '2', 'yes', '3', 'yes', '4', 'no', '9', 'no',
            ]);

            fclose($handle);
        }, 'question-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, string $tenant, Exam $exam): RedirectResponse
    {
        $request->validate([
            'questions_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        ['rows' => $rows, 'errors' => $errors] = $this->parse($request->file('questions_file')->getRealPath(), $exam);

        if (empty($rows) && empty($errors)) {
            $errors[] = 'The file has no data rows.';
        }

        if (! empty($errors)) {
            return back()->withErrors(['questions_file' => $errors])->withInput();
        }

        $position = $exam->questions()->count();

        DB::transaction(function () use ($exam, $rows, &$position) {
            foreach ($rows as $row) {
                $question = $exam->questions()->create([
                    'question_text' => $row['question_text'],
                    'answer_type' => $row['answer_type'],
                    'scoring_method' => $row['scoring_method'],
                    'exam_section_id' => $row['exam_section_id'],
                    'points' => $row['points'],
                    'position' => $position++,
                ]);

                foreach ($row['options'] as $i => $option) {
                    $question->options()->create([
                        'option_text' => $option['option_text'],
                        'is_correct' => $option['is_correct'],
                        'position' => $i,
                    ]);
                }
            }
        });

        $count = count($rows);

        return redirect()
            ->route('cbt.manage.exams.edit', $exam)
            ->with('status', "Imported {$count} ".Str::plural('question', $count).' from the CSV file.');
    }

    /**
     * Parses and fully validates the CSV before anything is written — either every
     * row is valid and gets imported, or nothing is, with row-numbered messages the
     * user can act on and re-upload. option_N/correct_N column pairs are discovered
     * from the header rather than hard-coded, so a template with more (or fewer)
     * option columns than the default still works.
     *
     * @return array{rows: list<array{question_text: string, answer_type: string, scoring_method: string, exam_section_id: int|null, options: list<array{option_text: string, is_correct: bool}>}>, errors: list<string>}
     */
    private function parse(string $path, Exam $exam): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['rows' => [], 'errors' => ['The uploaded file could not be read.']];
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return ['rows' => [], 'errors' => ['The file is empty.']];
        }

        $columns = array_map(fn ($name) => strtolower(trim((string) $name)), $header);
        $index = array_flip($columns);

        if (! array_key_exists('question_text', $index)) {
            fclose($handle);

            return ['rows' => [], 'errors' => ["Missing required column 'question_text'."]];
        }

        $optionNumbers = [];
        foreach ($columns as $name) {
            if (preg_match('/^option_(\d+)$/', $name, $matches)) {
                $optionNumbers[] = (int) $matches[1];
            }
        }
        sort($optionNumbers);
        $optionNumbers = array_unique($optionNumbers);

        $sectionsByTitle = $exam->sections->keyBy(fn ($section) => strtolower($section->title));

        $rows = [];
        $errors = [];
        $rowNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $cell = function (string $name) use ($data, $index): ?string {
                if (! array_key_exists($name, $index) || ! array_key_exists($index[$name], $data)) {
                    return null;
                }

                $value = trim((string) $data[$index[$name]]);

                return $value === '' ? null : $value;
            };

            $questionText = $cell('question_text');

            if ($questionText === null) {
                $errors[] = "Row {$rowNumber}: question_text is required.";

                continue;
            }

            $answerType = AnswerType::tryFrom(strtolower($cell('answer_type') ?? AnswerType::Single->value));

            if (! in_array($answerType, [AnswerType::Single, AnswerType::Multiple], true)) {
                $errors[] = "Row {$rowNumber}: invalid answer_type (use 'single' or 'multiple').";

                continue;
            }

            $scoringMethod = ScoringMethod::tryFrom(strtolower($cell('scoring_method') ?? ScoringMethod::AllOrNothing->value));

            if ($scoringMethod === null) {
                $errors[] = "Row {$rowNumber}: invalid scoring_method (use 'all_or_nothing' or 'partial_credit').";

                continue;
            }

            if ($answerType === AnswerType::Single) {
                $scoringMethod = ScoringMethod::AllOrNothing;
            }

            $pointsRaw = $cell('points');
            $points = $pointsRaw === null ? 1 : filter_var($pointsRaw, FILTER_VALIDATE_INT);

            if ($points === false || $points < 1) {
                $errors[] = "Row {$rowNumber}: points must be a whole number of at least 1.";

                continue;
            }

            $sectionId = null;
            $sectionName = $cell('section');

            if ($sectionName !== null) {
                $section = $sectionsByTitle->get(strtolower($sectionName));

                if ($section === null) {
                    $errors[] = "Row {$rowNumber}: section '{$sectionName}' does not exist on this exam — create it first or leave the column blank.";

                    continue;
                }

                $sectionId = $section->id;
            }

            $options = [];
            foreach ($optionNumbers as $number) {
                $text = $cell("option_{$number}");

                if ($text === null) {
                    continue;
                }

                $correct = strtolower($cell("correct_{$number}") ?? '');
                $options[] = [
                    'option_text' => e($text),
                    'is_correct' => in_array($correct, ['1', 'true', 'yes', 'y', 'x'], true),
                ];
            }

            if (count($options) < 2) {
                $errors[] = "Row {$rowNumber}: at least two answer options are required.";

                continue;
            }

            $correctCount = count(array_filter($options, fn ($option) => $option['is_correct']));

            if ($answerType === AnswerType::Single && $correctCount !== 1) {
                $errors[] = "Row {$rowNumber}: single-answer questions need exactly one correct option (found {$correctCount}).";

                continue;
            }

            if ($answerType === AnswerType::Multiple && $correctCount < 1) {
                $errors[] = "Row {$rowNumber}: at least one correct option is required.";

                continue;
            }

            $rows[] = [
                'question_text' => nl2br(e($questionText)),
                'answer_type' => $answerType->value,
                'scoring_method' => $scoringMethod->value,
                'exam_section_id' => $sectionId,
                'points' => $points,
                'options' => $options,
            ];
        }

        fclose($handle);

        return ['rows' => $rows, 'errors' => $errors];
    }
}
