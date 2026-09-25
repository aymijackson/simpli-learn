@props(['exam', 'best' => null])

{{-- Catalog card for a CBT exam. $best is the learner's best submitted score, if any. --}}
<a href="{{ route('cbt.exams.show', $exam) }}" {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl bg-white shadow-[0_1px_2px_rgb(15_23_42/0.04)] ring-1 ring-slate-200/80 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/5']) }}>
    <x-cover :seed="$exam->title" icon="clipboard-check" class="aspect-[16/7]" />

    <div class="flex flex-1 flex-col p-5">
        <h3 class="line-clamp-2 text-[15px] leading-snug font-semibold text-slate-900 group-hover:text-brand-700">{{ $exam->title }}</h3>
        @if ($exam->description)
            <p class="mt-1.5 line-clamp-2 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags($exam->description), 120) }}</p>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-4 text-xs text-slate-500">
            @isset($exam->questions_count)
                <span class="inline-flex items-center gap-1"><x-icon name="question" class="h-3.5 w-3.5" /> {{ $exam->questions_per_attempt ? min($exam->questions_per_attempt, $exam->questions_count) : $exam->questions_count }} questions</span>
            @endisset
            <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ $exam->duration_minutes }} min</span>
            <span class="inline-flex items-center gap-1"><x-icon name="flag" class="h-3.5 w-3.5" /> Pass {{ $exam->pass_percentage }}%</span>
        </div>

        @if ($best !== null)
            <div class="mt-3 flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs">
                <span class="text-slate-500">Your best</span>
                <span class="font-bold {{ $best >= $exam->pass_percentage ? 'text-emerald-600' : 'text-rose-600' }}">{{ $best }}%</span>
            </div>
        @endif
    </div>
</a>
