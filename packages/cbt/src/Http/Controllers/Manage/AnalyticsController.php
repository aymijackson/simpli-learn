<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $rows = Exam::withCount('questions')->get()->map(function (Exam $exam) {
            $submitted = $exam->attempts()->whereNotNull('submitted_at')->get(['id', 'user_id', 'score', 'started_at', 'submitted_at']);

            return (object) array_merge(
                ['exam' => $exam],
                $this->summarize($submitted, $exam->pass_percentage)
            );
        });

        return view('cbt::manage.analytics.index', ['rows' => $rows]);
    }

    public function show(Request $request, string $tenant, Exam $exam): View
    {
        $filtered = $this->filteredAttempts($exam, $request)->get();

        $attempts = $this->filteredAttempts($exam, $request)
            ->with('user')
            ->withCount('integrityEvents')
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('cbt::manage.analytics.show', [
            'exam' => $exam,
            'attempts' => $attempts,
            'stats' => $this->summarize($filtered, $exam->pass_percentage),
            'filters' => $request->only(['min_score', 'max_score', 'status', 'from', 'to']),
        ]);
    }

    public function integrityEvents(string $tenant, Exam $exam, ExamAttempt $attempt): View
    {
        abort_unless($attempt->exam_id === $exam->id, 404);

        return view('cbt::manage.analytics.integrity', [
            'exam' => $exam,
            'attempt' => $attempt->load('user'),
            'events' => $attempt->integrityEvents()->orderBy('created_at')->get(),
        ]);
    }

    public function exportCsv(Request $request, string $tenant, Exam $exam): StreamedResponse
    {
        $attempts = $this->filteredAttempts($exam, $request)->with('user')->latest('submitted_at')->get();

        return response()->streamDownload(function () use ($attempts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Learner', 'Email', 'Score (%)', 'Result', 'Started', 'Submitted', 'Time taken (min)']);

            foreach ($attempts as $attempt) {
                fputcsv($handle, [
                    $attempt->user->name,
                    $attempt->user->email,
                    $attempt->score,
                    $attempt->passed() ? 'Passed' : 'Failed',
                    $attempt->started_at->format('Y-m-d H:i'),
                    $attempt->submitted_at->format('Y-m-d H:i'),
                    round($attempt->durationMinutes(), 1),
                ]);
            }

            fclose($handle);
        }, Str::slug($exam->title).'-results-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request, string $tenant, Exam $exam): Response
    {
        $attempts = $this->filteredAttempts($exam, $request)->with('user')->latest('submitted_at')->get();

        $pdf = Pdf::loadView('cbt::manage.analytics.pdf', [
            'exam' => $exam,
            'attempts' => $attempts,
            'stats' => $this->summarize($attempts, $exam->pass_percentage),
        ]);

        return $pdf->download(Str::slug($exam->title).'-results-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * The one place filter logic lives — used by the on-screen shortlist
     * (paginated), and both exports (unpaginated), so "export" always
     * means exactly "what's currently filtered."
     */
    private function filteredAttempts(Exam $exam, Request $request): Builder
    {
        $request->validate([
            'min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(['passed', 'failed'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        // Built directly off the model (not $exam->attempts()) so this stays
        // a genuine Builder throughout: chaining ->where() etc. off a
        // relation collapses back to the HasMany relation object itself
        // (Laravel's Relation::__call keeps it relation-typed for further
        // relation-specific chaining), which doesn't satisfy this method's
        // Builder return type and throws a TypeError at the return.
        $query = ExamAttempt::query()->where('exam_id', $exam->id)->whereNotNull('submitted_at');

        if ($request->filled('min_score')) {
            $query->where('score', '>=', (int) $request->input('min_score'));
        }

        if ($request->filled('max_score')) {
            $query->where('score', '<=', (int) $request->input('max_score'));
        }

        if ($request->input('status') === 'passed') {
            $query->where('score', '>=', $exam->pass_percentage);
        } elseif ($request->input('status') === 'failed') {
            $query->where('score', '<', $exam->pass_percentage);
        }

        if ($request->filled('from')) {
            $query->whereDate('submitted_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('submitted_at', '<=', $request->input('to'));
        }

        return $query;
    }

    /**
     * Min/max/avg score and time-taken, plus pass rate and unique learner
     * count, over a set of already-submitted attempts. Shared by the
     * tenant-wide overview, the per-exam shortlist (recomputed over
     * whatever's currently filtered), and the PDF report, so the numbers
     * shown anywhere always mean the same thing.
     */
    private function summarize(Collection $submittedAttempts, int $passPercentage): array
    {
        $durations = $submittedAttempts->map(fn ($attempt) => $attempt->durationMinutes());

        return [
            'count' => $submittedAttempts->count(),
            'uniqueLearners' => $submittedAttempts->pluck('user_id')->unique()->count(),
            'passRate' => $submittedAttempts->isEmpty() ? null : (int) round(
                $submittedAttempts->filter(fn ($attempt) => $attempt->score >= $passPercentage)->count()
                    / $submittedAttempts->count() * 100
            ),
            'minScore' => $submittedAttempts->min('score'),
            'maxScore' => $submittedAttempts->max('score'),
            'avgScore' => $submittedAttempts->isEmpty() ? null : (int) round($submittedAttempts->avg('score')),
            'minMinutes' => $durations->isEmpty() ? null : round($durations->min(), 1),
            'maxMinutes' => $durations->isEmpty() ? null : round($durations->max(), 1),
            'avgMinutes' => $durations->isEmpty() ? null : round($durations->avg(), 1),
        ];
    }
}
