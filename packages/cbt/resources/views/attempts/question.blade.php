<x-app-layout :title="$exam->title">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $exam->title }}</h1>
            <p class="text-sm text-slate-500">Question {{ $page }} of {{ $totalPages }}</p>
        </div>
        <div id="countdown" class="rounded-lg bg-slate-900 px-3 py-1.5 font-mono text-sm font-semibold text-white">
            {{ $exam->duration_minutes }}:00
        </div>
    </div>

    @if ($exam->instructions && $page === 1)
        <x-card class="mb-6 bg-slate-50">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Instructions</p>
            <div class="rich-text text-sm text-slate-700">{!! $exam->instructions !!}</div>
        </x-card>
    @endif

    @include('cbt::attempts._progress_strip', ['currentPage' => $page])

    @if ($question->section)
        <div class="mb-4 rounded-lg bg-indigo-50 px-4 py-3">
            <p class="text-sm font-semibold text-indigo-900">{{ $question->section->title }}</p>
            @if ($question->section->instructions)
                <div class="rich-text mt-1 text-sm text-indigo-700">{!! $question->section->instructions !!}</div>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('cbt.attempts.questions.answer', [$attempt, $page]) }}" class="space-y-6">
        @csrf

        <x-card>
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Question {{ $page }}</p>
                    <div class="rich-text mt-1 text-sm font-semibold text-slate-900">{!! $question->question_text !!}</div>
                </div>
                <label class="flex shrink-0 items-center gap-1.5 text-xs font-medium text-amber-600">
                    <input type="checkbox" name="flagged" value="1" class="rounded border-amber-300 text-amber-600 focus:ring-amber-600" @checked($isFlagged)>
                    Flag
                </label>
            </div>
            <div class="mt-4 space-y-2">
                @foreach ($question->options as $option)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                        @if ($question->answer_type->value === 'single')
                            <input type="radio" name="answers" value="{{ $option->id }}" class="mt-0.5 text-emerald-600 focus:ring-emerald-600" @checked($selectedOptionIds->contains($option->id))>
                        @else
                            <input type="checkbox" name="answers[]" value="{{ $option->id }}" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-600" @checked($selectedOptionIds->contains($option->id))>
                        @endif
                        <span class="rich-text">{!! $option->option_text !!}</span>
                    </label>
                @endforeach
            </div>
        </x-card>

        <div class="flex items-center justify-between">
            <div>
                @if ($exam->allow_backward_navigation && $page > 1)
                    <x-button variant="secondary" :href="route('cbt.attempts.questions.show', [$attempt, $page - 1])">&larr; Previous</x-button>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('cbt.attempts.review', $attempt) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Review pane</a>
                <x-button type="submit">{{ $page >= $totalPages ? 'Save & review' : 'Save & continue' }} &rarr;</x-button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                const deadline = new Date(@js($attempt->started_at->copy()->addMinutes($exam->duration_minutes)->toIso8601String())).getTime();
                const el = document.getElementById('countdown');

                const tick = () => {
                    const remaining = deadline - Date.now();

                    if (remaining <= 0) {
                        el.textContent = "Time's up";
                        window.location.reload();
                        return;
                    }

                    const minutes = Math.floor(remaining / 60000);
                    const seconds = Math.floor((remaining % 60000) / 1000);
                    el.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    setTimeout(tick, 1000);
                };

                tick();
            })();
        </script>
    @endpush
</x-app-layout>
