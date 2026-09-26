@props(['course', 'progress' => null])

{{-- Catalog card for an LMS course. Pass $progress (0-100) for enrolled learners. --}}
<a href="{{ route('lms.courses.show', $course) }}" {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl bg-white shadow-[0_1px_2px_rgb(15_23_42/0.04)] ring-1 ring-slate-200/80 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/5']) }}>
    @if ($course->cover_image_path)
        <div class="relative aspect-[16/9] overflow-hidden bg-slate-100">
            <img src="{{ $course->coverUrl() }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
            @if ($progress === null && $course->pricing_policy->value !== 'paid')
                <span class="absolute bottom-3 left-4 rounded-full bg-black/40 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white backdrop-blur-sm">Free</span>
            @endif
        </div>
    @else
        <x-cover :seed="$course->title" icon="academic-cap" class="aspect-[16/9]" :label="$progress === null ? ($course->pricing_policy->value === 'paid' ? null : 'Free') : null" />
    @endif

    <div class="flex flex-1 flex-col p-5">
        @if ($course->category)
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-brand-600">{{ $course->category }}</p>
        @endif
        <h3 class="line-clamp-2 text-[15px] leading-snug font-semibold text-slate-900 group-hover:text-brand-700">{{ $course->title }}</h3>
        @if ($course->subtitle)
            <p class="mt-1.5 line-clamp-2 text-sm text-slate-500">{{ $course->subtitle }}</p>
        @elseif ($course->description)
            <p class="mt-1.5 line-clamp-2 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags($course->description), 120) }}</p>
        @endif
        @if ($course->instructor_name)
            <p class="mt-1.5 truncate text-xs text-slate-500">{{ $course->instructor_name }}</p>
        @endif
        @if (($course->reviews_count ?? 0) > 0)
            <p class="mt-2 flex items-center gap-1.5 text-xs">
                <span class="font-bold text-amber-700">{{ number_format($course->reviews_avg_stars, 1) }}</span>
                <x-stars :value="$course->reviews_avg_stars" size="h-3.5 w-3.5" />
                <span class="text-slate-400">({{ number_format($course->reviews_count) }})</span>
            </p>
        @endif

        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
            @isset($course->lessons_count)
                <span class="inline-flex items-center gap-1"><x-icon name="play" class="h-3.5 w-3.5" /> {{ $course->lessons_count }} {{ \Illuminate\Support\Str::plural('lesson', $course->lessons_count) }}</span>
            @endisset
            @if ($course->certificate_policy->value !== 'none')
                <span class="inline-flex items-center gap-1"><x-icon name="trophy" class="h-3.5 w-3.5" /> Certificate</span>
            @endif
            @if ($course->assessment_mode->value !== 'none')
                <span class="inline-flex items-center gap-1"><x-icon name="clipboard-check" class="h-3.5 w-3.5" /> Assessed</span>
            @endif
            @if ($course->durationLabel())
                <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ $course->durationLabel() }}</span>
            @endif
            @if ($course->level)
                <span class="inline-flex items-center gap-1"><x-icon name="chart-bar" class="h-3.5 w-3.5" /> {{ $course->level->label() }}</span>
            @endif
        </div>

        <div class="mt-auto pt-4">
            @if ($progress !== null)
                <x-progress :value="$progress" label />
                <p class="mt-2 text-xs font-semibold {{ $progress === 100 ? 'text-emerald-600' : 'text-brand-700' }}">
                    {{ $progress === 100 ? 'Completed' : ($progress === 0 ? 'Start course' : 'Continue learning') }} &rarr;
                </p>
            @elseif ($course->pricing_policy->value === 'paid')
                <p class="text-base font-bold text-slate-900">{{ $course->currency }} {{ number_format($course->price, 2) }}</p>
            @else
                <p class="text-base font-bold text-emerald-600">Free</p>
            @endif
        </div>
    </div>
</a>
