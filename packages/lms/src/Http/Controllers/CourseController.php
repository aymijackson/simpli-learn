<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Lms\Certificates\CourseCertificateService;
use Elibrary\Lms\Enums\CourseLevel;
use Elibrary\Lms\Enums\CoursePricingPolicy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Course::query()
            ->where('is_published', true)
            ->withCount(['lessons', 'enrollments', 'reviews'])
            ->withAvg('reviews', 'stars');

        $search = $request->string('q')->trim()->limit(100, '')->value();
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn ($inner) => $inner->where('title', 'like', $like)
                ->orWhere('subtitle', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('instructor_name', 'like', $like));
        }

        $category = $request->string('category')->trim()->value();
        if ($category !== '') {
            $query->where('category', $category);
        }

        $level = CourseLevel::tryFrom($request->string('level')->value());
        if ($level) {
            $query->where('level', $level->value);
        }

        $price = $request->string('price')->value();
        if (in_array($price, ['free', 'paid'], true)) {
            $query->where('pricing_policy', $price);
        }

        $sort = $request->string('sort')->value();
        match ($sort) {
            'popular' => $query->orderByDesc('enrollments_count'),
            'rating' => $query->orderByDesc('reviews_avg_stars')->orderByDesc('reviews_count'),
            'title' => $query->orderBy('title'),
            default => $query->latest(),
        };

        $courses = $query->get();
        $enrolledIds = Enrollment::where('user_id', $request->user()->id)->pluck('course_id');

        return view('lms::courses.index', [
            'courses' => $courses,
            'progress' => $courses->whereIn('id', $enrolledIds)
                ->mapWithKeys(fn (Course $course) => [$course->id => $course->progressPercentFor($request->user())]),
            'search' => $search,
            'activePrice' => in_array($price, ['free', 'paid'], true) ? $price : '',
            'activeSort' => in_array($sort, ['popular', 'rating', 'title'], true) ? $sort : 'newest',
            'activeCategory' => $category,
            'activeLevel' => $level?->value ?? '',
            'categories' => Course::where('is_published', true)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'totalPublished' => Course::where('is_published', true)->count(),
        ]);
    }

    public function show(Request $request, string $tenant, Course $course): View
    {
        $course->load(['lessons.attachments', 'modules'])->loadCount(['enrollments', 'reviews'])->loadAvg('reviews', 'stars');

        if ($course->isEnrolled($request->user())) {
            app(CourseCertificateService::class)->issueIfPassedAndFree($course, $request->user());
        }

        $reviews = $course->reviews()->with('user')->take(20)->get();

        return view('lms::courses.show', [
            'course' => $course,
            'reviews' => $reviews,
            'myReview' => $course->reviews()->where('user_id', $request->user()->id)->first(),
            // How many reviews gave each star rating, for the 5..1 breakdown bars.
            'starCounts' => $course->reviews()->reorder()->selectRaw('stars, COUNT(*) as total')->groupBy('stars')->pluck('total', 'stars'),
            'isEnrolled' => $course->isEnrolled($request->user()),
            'progress' => $course->progressPercentFor($request->user()),
            'isPassed' => $course->isPassedBy($request->user()),
            'certificate' => $course->certificateFor($request->user()),
        ]);
    }

    public function enroll(Request $request, string $tenant, Course $course): RedirectResponse
    {
        if ($course->pricing_policy === CoursePricingPolicy::Paid && ! $course->isEnrolled($request->user())) {
            return redirect()->route('lms.courses.purchase.create', $course);
        }

        $course->enrollments()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['enrolled_at' => now()],
        );

        return redirect()
            ->route('lms.courses.show', $course)
            ->with('status', "You're enrolled in {$course->title}.");
    }
}
