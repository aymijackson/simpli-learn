<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Star ratings and comments from enrolled learners. One review per person
 * per course (posting again updates it). Owners can remove any review.
 */
class CourseReviewController extends Controller
{
    public function store(Request $request, string $tenant, Course $course): RedirectResponse
    {
        abort_unless($course->isEnrolled($request->user()), 403, 'Enroll in this course to review it.');

        $validated = $request->validate([
            'stars' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        CourseReview::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $request->user()->id],
            ['tenant_id' => app(Tenancy::class)->id(), 'stars' => $validated['stars'], 'comment' => $validated['comment'] ?? null],
        );

        return redirect(route('lms.courses.show', $course).'#reviews')->with('status', 'Thanks for your review!');
    }

    public function destroy(Request $request, string $tenant, Course $course, CourseReview $review): RedirectResponse
    {
        abort_unless($review->course_id === $course->id, 404);

        $user = $request->user();
        $isOwnReview = $review->user_id === $user->id;
        abort_unless($isOwnReview || $user->isOwner(), 403);

        if (! $isOwnReview) {
            ActivityLog::record('courses.review_removed', "Removed a {$review->stars}-star review of {$course->title} by ".($review->user?->name ?? 'a learner'), $course);
        }

        $review->delete();

        return redirect(route('lms.courses.show', $course).'#reviews')->with('status', 'Review removed.');
    }
}
