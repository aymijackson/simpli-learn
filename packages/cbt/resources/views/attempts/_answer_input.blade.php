{{--
    The answer control for one question, by answer type.
    Expects $question and $name (the form field name); optional $selected
    (option ids already chosen) and $text (a written answer already saved).
--}}
@php
    $type = $question->answer_type;
    $selected = $selected ?? collect();
    $text = $text ?? null;
@endphp

@if ($type === \Elibrary\Cbt\Enums\AnswerType::ShortAnswer)
    <label class="block">
        <span class="sr-only">Your answer</span>
        <input type="text" name="{{ $name }}" value="{{ $text }}" maxlength="500" autocomplete="off" data-answer-input
               placeholder="Type your answer"
               class="block w-full rounded-lg border-0 px-4 py-3 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
    </label>
@elseif ($type === \Elibrary\Cbt\Enums\AnswerType::Essay)
    <label class="block">
        <span class="sr-only">Your answer</span>
        <textarea name="{{ $name }}" rows="9" maxlength="20000" data-answer-input data-word-count
                  placeholder="Write your answer here"
                  class="block w-full rounded-lg border-0 px-4 py-3 text-sm leading-relaxed text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ $text }}</textarea>
    </label>
    <p class="text-right text-xs text-slate-400" data-word-count-for="{{ $name }}">0 words</p>
    @once
        @push('scripts')
            <script>
                document.querySelectorAll('textarea[data-word-count]').forEach((area) => {
                    const counter = document.querySelector(`[data-word-count-for="${area.name}"]`);
                    const update = () => {
                        const words = area.value.trim() ? area.value.trim().split(/\s+/).length : 0;
                        counter.textContent = `${words} ${words === 1 ? 'word' : 'words'}`;
                    };
                    area.addEventListener('input', update);
                    update();
                });
            </script>
        @endpush
    @endonce
    <p class="text-xs text-slate-500">This answer is marked by hand, so your result will show "Awaiting marking" until it has been marked.</p>
@else
    @foreach ($question->options as $option)
        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
            @if ($type->isSingleChoice())
                <input type="radio" name="{{ $name }}" value="{{ $option->id }}" data-answer-input class="mt-0.5 text-emerald-600 focus:ring-emerald-600" @checked($selected->contains($option->id))>
            @else
                <input type="checkbox" name="{{ $name }}[]" value="{{ $option->id }}" data-answer-input class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-600" @checked($selected->contains($option->id))>
            @endif
            <span class="rich-text">{!! $option->option_text !!}</span>
        </label>
    @endforeach
@endif
