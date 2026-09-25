<x-app-layout :title="$exam->title">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $exam->title }}</h1>
            <p class="text-sm text-slate-500">Answer every question, then submit.</p>
        </div>
        <div id="countdown" class="rounded-lg bg-slate-900 px-3 py-1.5 font-mono text-sm font-semibold text-white">
            {{ $exam->duration_minutes }}:00
        </div>
    </div>

    @if ($exam->instructions)
        <x-card class="mb-6 bg-slate-50">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Instructions</p>
            <div class="rich-text text-sm text-slate-700">{!! $exam->instructions !!}</div>
        </x-card>
    @endif

    <div id="review-strip" class="mb-6 flex flex-wrap gap-2"></div>

    <form id="exam-form" method="POST" action="{{ route('cbt.attempts.submit', $attempt) }}" class="space-y-6">
        @csrf

        @php($currentSectionId = 'unset')
        @foreach ($questions as $question)
            @if ($question->exam_section_id !== $currentSectionId)
                @php($currentSectionId = $question->exam_section_id)
                @if ($question->section)
                    <div class="rounded-lg bg-indigo-50 px-4 py-3">
                        <p class="text-sm font-semibold text-indigo-900">{{ $question->section->title }}</p>
                        @if ($question->section->instructions)
                            <div class="rich-text mt-1 text-sm text-indigo-700">{!! $question->section->instructions !!}</div>
                        @endif
                    </div>
                @endif
            @endif

            <x-card id="question-{{ $question->id }}" data-question-card data-question-id="{{ $question->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Question {{ $loop->iteration }}</p>
                        <div class="rich-text mt-1 text-sm font-semibold text-slate-900">{!! $question->question_text !!}</div>
                    </div>
                    <label class="flex shrink-0 items-center gap-1.5 text-xs font-medium text-amber-600">
                        <input type="checkbox" name="flagged[]" value="{{ $question->id }}" data-flag-input class="rounded border-amber-300 text-amber-600 focus:ring-amber-600">
                        Flag
                    </label>
                </div>
                <div class="mt-4 space-y-2">
                    @foreach ($question->options as $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                            @if ($question->answer_type->value === 'single')
                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" data-answer-input class="mt-0.5 text-emerald-600 focus:ring-emerald-600">
                            @else
                                <input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option->id }}" data-answer-input class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-600">
                            @endif
                            <span class="rich-text">{!! $option->option_text !!}</span>
                        </label>
                    @endforeach
                </div>
            </x-card>
        @endforeach

        <x-button type="submit" class="w-full">Submit exam</x-button>
    </form>

    @push('scripts')
        <script>
            (function () {
                const deadline = new Date(@js($attempt->started_at->copy()->addMinutes($exam->duration_minutes)->toIso8601String())).getTime();
                const el = document.getElementById('countdown');
                const form = document.getElementById('exam-form');

                const tick = () => {
                    const remaining = deadline - Date.now();

                    if (remaining <= 0) {
                        el.textContent = "Time's up";
                        form.submit();
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
        <script>
            (function () {
                const strip = document.getElementById('review-strip');
                const cards = Array.from(document.querySelectorAll('[data-question-card]'));

                const isAnswered = (card) => Array.from(card.querySelectorAll('[data-answer-input]')).some((input) => input.checked);
                const isFlagged = (card) => card.querySelector('[data-flag-input]').checked;

                const render = () => {
                    strip.innerHTML = '';
                    cards.forEach((card, index) => {
                        const link = document.createElement('a');
                        link.href = '#question-' + card.dataset.questionId;
                        link.textContent = index + 1;
                        link.className = 'flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-semibold ' + (
                            isFlagged(card)
                                ? 'border-amber-400 bg-amber-50 text-amber-700'
                                : isAnswered(card)
                                    ? 'border-emerald-400 bg-emerald-50 text-emerald-700'
                                    : 'border-slate-200 bg-white text-slate-500'
                        );
                        strip.appendChild(link);
                    });
                };

                cards.forEach((card) => card.addEventListener('change', render));
                render();
            })();
        </script>
    @endpush

    @include('cbt::attempts._integrity_monitor')
</x-app-layout>
