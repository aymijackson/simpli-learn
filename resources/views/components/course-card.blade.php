@props(['course', 'progress' => null])

{{-- Catalog card for an LMS course. Pass $progress (0-100) for enrolled learners. --}}
<a href="{{ route('lms.courses.show', $course) }}" {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl bg-white shadow-[0_1px_2px_rgb(15_23_42/0.04)] ring-1 ring-slate-200/80 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/5']) }}>
    <x-cover :seed="$course->title" icon="academic-cap" class="aspect-[16/9]" :label="$progress === null ? ($course->pricing_policy->value === 'paid' ? null : 'Free') : null" />

    <div class="flex flex-1 flex-col p-5">
        <h3 class="line-clamp-2 text-[15px] leading-snug font-semibold text-slate-900 group-hover:text-brand-700">{{ $course->title }}</h3>
        @if ($course->description)
            <p class="mt-1.5 line-clamp-2 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags($course->description), 120) }}</p>
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
