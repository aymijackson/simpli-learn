<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Enums\NavigationMode;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        return view('cbt::manage.exams.index', [
            'exams' => Exam::withCount('questions')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('cbt::manage.exams.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $exam = Exam::create($this->validated($request));

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Exam created.');
    }

    public function edit(string $tenant, Exam $exam): View
    {
        return view('cbt::manage.exams.edit', [
            'exam' => $exam,
            'questions' => $exam->questions()->withCount('options')->get(),
            'sections' => $exam->sections,
        ]);
    }

    public function update(Request $request, string $tenant, Exam $exam): RedirectResponse
    {
        $exam->update($this->validated($request, $exam));

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Exam updated.');
    }

    public function destroy(string $tenant, Exam $exam): RedirectResponse
    {
        $exam->delete();

        return redirect()->route('cbt.manage.exams.index')->with('status', 'Exam deleted.');
    }

    private function validated(Request $request, ?Exam $exam = null): array
    {
        $tenantId = app(Tenancy::class)->id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'alpha_dash', 'max:255',
                Rule::unique('exams', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($exam),
            ],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'pass_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'navigation_mode' => ['nullable', Rule::in(array_column(NavigationMode::cases(), 'value'))],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'questions_per_attempt' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['enforce_time_limit'] = $request->boolean('enforce_time_limit');
        $validated['navigation_mode'] = $validated['navigation_mode'] ?? NavigationMode::AllAtOnce->value;
        $validated['allow_backward_navigation'] = $validated['navigation_mode'] === NavigationMode::OneAtATime->value
            ? $request->boolean('allow_backward_navigation')
            : true;

        $validated['allow_retakes'] = $request->boolean('allow_retakes');
        $validated['max_attempts'] = $validated['allow_retakes'] ? $validated['max_attempts'] : null;
        $validated['randomize_questions'] = $request->boolean('randomize_questions');

        return $validated;
    }
}
