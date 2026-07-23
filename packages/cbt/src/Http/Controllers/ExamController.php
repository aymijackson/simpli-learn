<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        $exams = Exam::query()
            ->where('is_published', true)
            ->withCount('questions')
            ->get();

        return view('cbt::exams.index', [
            'exams' => $exams,
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
