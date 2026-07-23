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

<x-input type="number" name="position" label="Position" value="{{ old('position', $question->position ?? $nextPosition ?? 0) }}" min="0" required />

@push('scripts')
    <script>
        (function () {
            const answerTypeField = document.getElementById('answer-type');
            const scoringMethodField = document.getElementById('scoring-method-field');
            answerTypeField.addEventListener('change', () => {
                scoringMethodField.classList.toggle('hidden', answerTypeField.value !== 'multiple');
            });
        })();
    </script>
@endpush
