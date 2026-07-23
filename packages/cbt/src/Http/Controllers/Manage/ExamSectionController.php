<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExamSectionController extends Controller
{
    public function store(Request $request, string $tenant, Exam $exam): RedirectResponse
    {
        $exam->sections()->create($this->validated($request));

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Section added.');
    }

    public function update(Request $request, string $tenant, Exam $exam, ExamSection $section): RedirectResponse
    {
        abort_unless($section->exam_id === $exam->id, 404);

        $section->update($this->validated($request));

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Section updated.');
    }

    public function destroy(string $tenant, Exam $exam, ExamSection $section): RedirectResponse
    {
        abort_unless($section->exam_id === $exam->id, 404);

        $section->delete();

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Section deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'position' => ['required', 'integer', 'min:0'],
        ]);
    }
}
