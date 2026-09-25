<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $query = Exam::query()
            ->where('is_published', true)
            ->withCount('questions')
            ->orderBy('title');

        $search = $request->string('q')->trim()->limit(100, '')->value();
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn ($inner) => $inner->where('title', 'like', $like)->orWhere('description', 'like', $like));
        }

        $exams = $query->get();

        return view('cbt::exams.index', [
            'exams' => $exams,
            'search' => $search,
            // The learner's best submitted score per exam, for the "Your best" strip on each card.
            'bestScores' => ExamAttempt::query()
                ->where('user_id', $request->user()->id)
                ->whereNotNull('submitted_at')
                ->whereIn('exam_id', $exams->pluck('id'))
                ->selectRaw('exam_id, MAX(score) as best')
                ->groupBy('exam_id')
                ->pluck('best', 'exam_id'),
        ]);
    }

    public function show(Request $request, string $tenant, Exam $exam): View
    {
        $attempts = $exam->attemptsFor($request->user())->get();
        $hasActiveAttempt = $attempts->contains(fn ($attempt) => ! $attempt->isSubmitted());

        return view('cbt::exams.show', [
            'exam' => $exam,
            'attempts' => $attempts,
            'startBlockReason' => $hasActiveAttempt ? null : $exam->startBlockReason($request->user()),
        ]);
    }
}
