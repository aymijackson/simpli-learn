<x-editor name="question_text" label="Question" :value="old('question_text', $question->question_text ?? '')" />

@php($currentAnswerType = old('answer_type', $question->answer_type?->value ?? 'single'))

<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Answer type</label>
    <select id="answer-type" name="answer_type" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @foreach (\Elibrary\Cbt\Enums\AnswerType::cases() as $type)
            <option value="{{ $type->value }}" @selected($currentAnswerType === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </select>
    <ul class="mt-1 space-y-0.5 text-xs text-slate-500">
        @foreach (\Elibrary\Cbt\Enums\AnswerType::cases() as $type)
            <li><span class="font-medium text-slate-600">{{ $type->label() }}:</span> {{ $type->description() }}</li>
        @endforeach
    </ul>
</div>

<div id="scoring-method-field" class="{{ $currentAnswerType === 'multiple' ? '' : 'hidden' }}">
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Scoring method</label>
    <select name="scoring_method" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @foreach (\Elibrary\Cbt\Enums\ScoringMethod::cases() as $method)
            <option value="{{ $method->value }}" @selected(old('scoring_method', $question->scoring_method?->value ?? 'all_or_nothing') === $method->value)>
                {{ $method->label() }}
            </option>
        @endforeach
    </select>
    <ul class="mt-1 space-y-0.5 text-xs text-slate-500">
        @foreach (\Elibrary\Cbt\Enums\ScoringMethod::cases() as $method)
            <li><span class="font-medium text-slate-600">{{ $method->label() }}:</span> {{ $method->description() }}</li>
        @endforeach
    </ul>
</div>

@php($savedOptions = isset($question) ? $question->options : collect())

<div id="true-false-field" class="{{ $currentAnswerType === 'true_false' ? '' : 'hidden' }}">
    <p class="mb-1.5 block text-sm font-medium text-slate-700">Correct answer</p>
    @php($currentTf = old('correct_answer', $savedOptions->firstWhere('is_correct', true) ? strtolower($savedOptions->firstWhere('is_correct', true)->option_text) : null))
    <div class="flex gap-3">
        @foreach (['true' => 'True', 'false' => 'False'] as $value => $label)
            <label class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-800 has-[:checked]:ring-brand-500">
                <input type="radio" name="correct_answer" value="{{ $value }}" class="text-brand-600 focus:ring-brand-600" @checked($currentTf === $value)>
                {{ $label }}
            </label>
        @endforeach
    </div>
    @error('correct_answer')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div id="short-answer-field" class="{{ $currentAnswerType === 'short_answer' ? '' : 'hidden' }}">
    <label for="accepted-answers" class="mb-1.5 block text-sm font-medium text-slate-700">Accepted answers</label>
    <textarea id="accepted-answers" name="accepted_answers" rows="4" placeholder="One per line, e.g.&#10;phishing&#10;a phishing attack"
              class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">{{ old('accepted_answers', ($question->answer_type ?? null)?->value === 'short_answer' ? $savedOptions->pluck('option_text')->implode("\n") : '') }}</textarea>
    <p class="mt-1 text-xs text-slate-500">One per line. Matching ignores capital letters, extra spaces and a trailing full stop — add spelling variants you'll accept.</p>
    @error('accepted_answers')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div id="essay-field" class="{{ $currentAnswerType === 'essay' ? '' : 'hidden' }}">
    <label for="marking-guide" class="mb-1.5 block text-sm font-medium text-slate-700">Marking guide (optional)</label>
    <textarea id="marking-guide" name="marking_guide" rows="4" placeholder="What a full-marks answer covers, for whoever marks it"
              class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">{{ old('marking_guide', $question->marking_guide ?? '') }}</textarea>
    <p class="mt-1 text-xs text-slate-500">Shown only to markers. Essays are marked by hand in <strong>Marking</strong>; the learner's score is held until then.</p>
    @error('marking_guide')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

@if ($sections->isNotEmpty())
    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Section (optional)</label>
        <select name="exam_section_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
            <option value="">None</option>
            @foreach ($sections as $section)
                <option value="{{ $section->id }}" @selected((string) old('exam_section_id', $question->exam_section_id ?? '') === (string) $section->id)>{{ $section->title }}</option>
            @endforeach
        </select>
    </div>
@endif

<div class="grid gap-5 sm:grid-cols-2">
    <x-input type="number" name="position" label="Position" value="{{ old('position', $question->position ?? $nextPosition ?? 0) }}" min="0" required />
    <div>
        <x-input type="number" name="points" label="Points" value="{{ old('points', $question->points ?? 1) }}" min="1" required />
        <p class="mt-1 text-xs text-slate-500">How much this question counts toward the final score relative to the others.</p>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const answerTypeField = document.getElementById('answer-type');
            const fields = {
                'scoring-method-field': 'multiple',
                'true-false-field': 'true_false',
                'short-answer-field': 'short_answer',
                'essay-field': 'essay',
            };
            answerTypeField.addEventListener('change', () => {
                for (const [id, type] of Object.entries(fields)) {
                    document.getElementById(id).classList.toggle('hidden', answerTypeField.value !== type);
                }
                document.getElementById('options-editor')?.classList.toggle('hidden', ! ['single', 'multiple'].includes(answerTypeField.value));
            });
        })();
    </script>
@endpush
