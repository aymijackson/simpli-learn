@php($questions = $exam->orderedQuestions())
<div class="mb-6 flex flex-wrap gap-2">
    @foreach ($questions as $index => $question)
        @php($answer = $attempt->answers->firstWhere('question_id', $question->id))
        @php($answered = $answer && $answer->selectedOptions->isNotEmpty())
        @php($flagged = (bool) $answer?->is_flagged)
        @php($isCurrent = isset($currentPage) && $currentPage === $index + 1)
        @php($reachable = $exam->allow_backward_navigation || $isCurrent)
        @php($classes = 'flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-semibold '
            . ($isCurrent ? 'border-brand-600 ring-2 ring-brand-200 ' : '')
            . ($flagged ? 'border-amber-400 bg-amber-50 text-amber-700' : ($answered ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500')))
        @if ($reachable)
            <a href="{{ route('cbt.attempts.questions.show', [$attempt, $index + 1]) }}" class="{{ $classes }}">{{ $index + 1 }}</a>
        @else
            <span class="{{ $classes }} cursor-not-allowed opacity-60">{{ $index + 1 }}</span>
        @endif
    @endforeach
</div>
