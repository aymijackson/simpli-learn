<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every active person in the workspace against one exam: passed, failed,
 * still sitting it, or not attempted — the compliance view that the
 * attempt-based analytics can't show.
 */
class ExamPeopleReportController extends Controller
{
    public const LABELS = [
        'passed' => 'Passed',
        'awaiting_marking' => 'Awaiting marking',
        'failed' => 'Not passed yet',
        'in_progress' => 'In progress',
        'not_attempted' => 'Not attempted',
    ];

    public function show(Request $request, string $tenant, Exam $exam): View
    {
        $rows = $this->rows($exam);
        $filter = $request->string('status')->value();

        return view('cbt::manage.analytics.people', [
            'exam' => $exam,
            'rows' => array_key_exists($filter, self::LABELS) ? $rows->where('status', $filter)->values() : $rows,
            'counts' => ['all' => $rows->count()] + collect(self::LABELS)->map(fn ($label, $status) => $rows->where('status', $status)->count())->all(),
            'activeFilter' => array_key_exists($filter, self::LABELS) ? $filter : 'all',
            'passRate' => $rows->count() ? (int) round($rows->where('status', 'passed')->count() / $rows->count() * 100) : 0,
        ]);
    }

    public function export(string $tenant, Exam $exam): StreamedResponse
    {
        $rows = $this->rows($exam);
        ActivityLog::record('reports.exported', "Downloaded the results-by-person report for {$exam->title}", $exam);

        return response()->streamDownload(function () use ($rows, $exam) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Exam', 'Name', 'Email', 'Status', 'Best score %', 'Pass mark %', 'Attempts', 'Passed on', 'Last attempt']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $exam->title,
                    $row['user']->name,
                    $row['user']->email,
                    self::LABELS[$row['status']],
                    $row['best'],
                    $exam->pass_percentage,
                    $row['attempts'],
                    $row['passed_at']?->format('Y-m-d H:i'),
                    $row['last_at']?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'results-'.$exam->slug.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** @return Collection<int, array{user: \App\Models\User, status: string, best: ?int, attempts: int, passed_at: ?Carbon, last_at: ?Carbon}> */
    private function rows(Exam $exam): Collection
    {
        $people = app(Tenancy::class)->current()->users()->active()->orderBy('name')->get();

        $attempts = ExamAttempt::where('exam_id', $exam->id)
            ->whereIn('user_id', $people->pluck('id'))
            ->get(['user_id', 'score', 'submitted_at', 'started_at', 'needs_marking'])
            ->groupBy('user_id');

        $order = array_keys(self::LABELS);

        return $people->map(function ($user) use ($attempts, $exam) {
            $mine = $attempts->get($user->id, collect());
            $submitted = $mine->whereNotNull('submitted_at')->where('needs_marking', false);
            $awaiting = $mine->whereNotNull('submitted_at')->where('needs_marking', true);
            $best = $submitted->max('score');
            $passing = $submitted->filter(fn ($attempt) => $attempt->score >= $exam->pass_percentage);

            $status = match (true) {
                $passing->isNotEmpty() => 'passed',
                $awaiting->isNotEmpty() => 'awaiting_marking',
                $submitted->isNotEmpty() => 'failed',
                $mine->isNotEmpty() => 'in_progress',
                default => 'not_attempted',
            };

            return [
                'user' => $user,
                'status' => $status,
                'best' => $best === null ? null : (int) $best,
                'attempts' => $mine->whereNotNull('submitted_at')->count(),
                'passed_at' => $passing->min('submitted_at'),
                'last_at' => $mine->max(fn ($attempt) => $attempt->submitted_at ?? $attempt->started_at),
            ];
        })->sortBy(fn ($row) => [array_search($row['status'], $order), $row['user']->name])->values();
    }
}
