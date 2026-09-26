{{-- Ratings and reviews on the course page. Expects $course (with reviews_count / reviews_avg_stars), $reviews, $myReview, $starCounts, $isEnrolled. --}}
<section id="reviews" class="scroll-mt-24 rounded-2xl bg-white p-6 ring-1 ring-slate-200/80 sm:p-8">
    <h2 class="text-xl font-bold text-slate-900">Learner reviews</h2>

    @if ($course->reviews_count > 0)
        <div class="mt-5 grid gap-6 sm:grid-cols-[auto_1fr] sm:items-center">
            <div class="text-center sm:pr-6">
                <p class="text-5xl font-bold tracking-tight text-slate-900">{{ number_format($course->reviews_avg_stars, 1) }}</p>
                <x-stars :value="$course->reviews_avg_stars" class="mt-2" />
                <p class="mt-1 text-xs text-slate-500">{{ number_format($course->reviews_count) }} {{ \Illuminate\Support\Str::plural('rating', $course->reviews_count) }}</p>
            </div>
            <div class="space-y-1.5">
                @for ($stars = 5; $stars >= 1; $stars--)
                    @php($share = $course->reviews_count ? (int) round(($starCounts[$stars] ?? 0) / $course->reviews_count * 100) : 0)
                    <div class="flex items-center gap-3 text-xs text-slate-600">
                        <span class="w-12 shrink-0">{{ $stars }} {{ $stars === 1 ? 'star' : 'stars' }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-amber-400" style="width: {{ $share }}%"></div></div>
                        <span class="w-9 text-right">{{ $share }}%</span>
                    </div>
                @endfor
            </div>
        </div>
    @else
        <p class="mt-2 text-sm text-slate-500">No reviews yet{{ $isEnrolled ? ' — be the first to share what you thought.' : '.' }}</p>
    @endif

    @if ($isEnrolled)
        <form method="POST" action="{{ route('lms.courses.reviews.store', $course) }}" class="mt-6 rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200">
            @csrf
            <p class="text-sm font-semibold text-slate-900">{{ $myReview ? 'Update your review' : 'Rate this course' }}</p>
            <div class="mt-3 flex flex-row-reverse justify-end gap-1" role="radiogroup" aria-label="Your rating">
                {{-- Reversed so the CSS sibling selector can fill every star up to the hovered/checked one. --}}
                @for ($i = 5; $i >= 1; $i--)
                    <input type="radio" id="stars-{{ $i }}" name="stars" value="{{ $i }}" class="sr-only" @checked(old('stars', $myReview?->stars) == $i) required>
                    <label for="stars-{{ $i }}" title="{{ $i }} {{ $i === 1 ? 'star' : 'stars' }}"
                           class="cursor-pointer text-3xl leading-none text-slate-300 transition hover:text-amber-400 [&:hover~label]:text-amber-400 [input:checked~&]:text-amber-400">&#9733;</label>
                @endfor
            </div>
            @error('stars')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <textarea name="comment" rows="3" maxlength="2000" placeholder="What did you like? What could be better? (optional)"
                      class="mt-3 block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">{{ old('comment', $myReview?->comment) }}</textarea>
            <x-button type="submit" size="sm" class="mt-3">{{ $myReview ? 'Update review' : 'Post review' }}</x-button>
        </form>
    @endif

    @if ($reviews->isNotEmpty())
        <ul class="mt-6 divide-y divide-slate-100">
            @foreach ($reviews as $review)
                <li class="py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                {{ collect(explode(' ', (string) $review->user?->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?' }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $review->user?->name ?? 'A learner' }}</p>
                                <p class="flex items-center gap-2 text-xs text-slate-500"><x-stars :value="$review->stars" size="h-3.5 w-3.5" /> {{ $review->updated_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @if ($review->user_id === auth()->id() || auth()->user()->isOwner())
                            <form method="POST" action="{{ route('lms.courses.reviews.destroy', [$course, $review]) }}" onsubmit="return confirm('Remove this review?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-slate-400 hover:text-red-600">Remove</button>
                            </form>
                        @endif
                    </div>
                    @if ($review->comment)
                        <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $review->comment }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
