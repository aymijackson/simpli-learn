<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Lms\Enums\CoursePricingPolicy;
use Elibrary\Lms\Models\LearningPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function index(Request $request): View
    {
        $paths = LearningPath::where('is_published', true)
            ->with(['courses' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('title')
            ->get()
            ->filter(fn (LearningPath $path) => $path->courses->isNotEmpty());

        return view('lms::paths.index', [
            'paths' => $paths->map(fn (LearningPath $path) => [
                'path' => $path,
                'percent' => LearningPath::percentOf($path->stepsFor($request->user())),
            ]),
        ]);
    }

    public function show(Request $request, string $tenant, LearningPath $path): View
    {
        abort_unless($path->is_published || $request->user()->isOwner(), 404);

        $path->load(['courses' => fn ($query) => $query->where('is_published', true)->withCount('lessons')]);
        $steps = $path->stepsFor($request->user());

        return view('lms::paths.show', [
            'path' => $path,
            'steps' => $steps,
            'percent' => LearningPath::percentOf($steps),
            'next' => $steps->first(fn ($step) => in_array($step['status'], ['in_progress', 'not_started'], true)),
        ]);
    }

    /**
     * Start (or continue) the path: enrol in the next free course that's open
     * to this person and go there. Paid courses go to their checkout page.
     */
    public function start(Request $request, string $tenant, LearningPath $path): RedirectResponse
    {
        abort_unless($path->is_published, 404);

        $path->load(['courses' => fn ($query) => $query->where('is_published', true)]);
        $step = $path->stepsFor($request->user())->first(fn ($step) => in_array($step['status'], ['in_progress', 'not_started'], true));

        if (! $step) {
            return redirect()->route('lms.paths.show', $path)->with('status', 'Nothing more to start here right now.');
        }

        $course = $step['course'];

        if ($step['status'] === 'not_started') {
            if ($course->pricing_policy === CoursePricingPolicy::Paid) {
                return redirect()->route('lms.courses.purchase.create', $course);
            }

            $course->enrollments()->firstOrCreate(['user_id' => $request->user()->id], ['enrolled_at' => now()]);
        }

        return redirect()->route('lms.courses.show', $course)->with('status', "Part of {$path->title}: {$course->title}.");
    }
}
