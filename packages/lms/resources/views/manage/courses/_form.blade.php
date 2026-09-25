<x-input type="text" name="title" id="course-title" label="Title" value="{{ old('title', $course->title ?? '') }}" required autofocus />

<x-input type="text" name="slug" id="course-slug" label="Slug" value="{{ old('slug', $course->slug ?? '') }}" required />

<x-editor name="description" label="Description (optional)" :value="old('description', $course->description ?? '')" />

<label class="flex items-center gap-2 text-sm text-slate-600">
    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_published', $course->is_published ?? false))>
    Published (visible to learners)
</label>

@php($currentMode = old('assessment_mode', $course->assessment_mode?->value ?? 'none'))

<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Exam requirements</label>
    <select id="assessment-mode" name="assessment_mode" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @foreach (\Elibrary\Lms\Enums\AssessmentMode::cases() as $mode)
            <option value="{{ $mode->value }}" @selected($currentMode === $mode->value)>{{ $mode->label() }}</option>
        @endforeach
    </select>
    <ul class="mt-1 space-y-0.5 text-xs text-slate-500">
        @foreach (\Elibrary\Lms\Enums\AssessmentMode::cases() as $mode)
            <li><span class="font-medium text-slate-600">{{ $mode->label() }}:</span> {{ $mode->description() }}</li>
        @endforeach
    </ul>
</div>

<div id="final-exam-field" class="{{ $currentMode === 'course_final' ? '' : 'hidden' }}">
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Final exam</label>
    <select name="final_exam_id" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        <option value="">Select an exam&hellip;</option>
        @foreach ($exams as $exam)
            <option value="{{ $exam->id }}" @selected((string) old('final_exam_id', $course->final_exam_id ?? '') === (string) $exam->id)>{{ $exam->title }}</option>
        @endforeach
    </select>
    @if ($exams->isEmpty())
        <p class="mt-1 text-xs text-amber-600">No exams exist yet for this workspace — create one in the CBT module first.</p>
    @endif
</div>

@php($currentPricingPolicy = old('pricing_policy', $course->pricing_policy?->value ?? 'free'))

<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Pricing</label>
    <select id="pricing-policy" name="pricing_policy" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @foreach (\Elibrary\Lms\Enums\CoursePricingPolicy::cases() as $policy)
            <option value="{{ $policy->value }}" @selected($currentPricingPolicy === $policy->value)>{{ $policy->label() }}</option>
        @endforeach
    </select>
</div>

<div id="course-price-field" class="grid gap-5 sm:grid-cols-2 {{ $currentPricingPolicy === 'free' ? 'hidden' : '' }}">
    <x-input type="number" name="price" label="Price" value="{{ old('price', $course->price ?? '') }}" min="0" step="0.01" />
    <x-input type="text" name="currency" label="Currency (3-letter code)" value="{{ old('currency', $course->currency ?? '') }}" maxlength="3" />
</div>

<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Completion certificate</label>
    <select id="course-certificate-policy" name="certificate_policy" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @foreach (\Elibrary\Lms\Enums\CourseCertificatePolicy::cases() as $policy)
            <option value="{{ $policy->value }}" @selected(old('certificate_policy', $course->certificate_policy?->value ?? 'none') === $policy->value)>{{ $policy->label() }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Independent of pricing above — a free course can still charge only for its completion certificate.</p>
</div>

@php($currentCertPolicy = old('certificate_policy', $course->certificate_policy?->value ?? 'none'))

<div id="course-certificate-price-field" class="grid gap-5 sm:grid-cols-2 {{ $currentCertPolicy === 'paid' ? '' : 'hidden' }}">
    <x-input type="number" name="certificate_price" label="Certificate price" value="{{ old('certificate_price', $course->certificate_price ?? '') }}" min="0" step="0.01" />
    <x-input type="text" name="certificate_currency" label="Currency (3-letter code)" value="{{ old('certificate_currency', $course->certificate_currency ?? '') }}" maxlength="3" />
</div>

@push('scripts')
    <script>
        (function () {
            const titleField = document.getElementById('course-title');
            const slugField = document.getElementById('course-slug');
            let slugTouched = slugField.value.length > 0;

            const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

            titleField.addEventListener('input', () => {
                if (!slugTouched) {
                    slugField.value = slugify(titleField.value);
                }
            });

            slugField.addEventListener('input', () => {
                slugTouched = true;
            });

            const modeField = document.getElementById('assessment-mode');
            const finalExamField = document.getElementById('final-exam-field');
            modeField.addEventListener('change', () => {
                finalExamField.classList.toggle('hidden', modeField.value !== 'course_final');
            });

            const pricingPolicyField = document.getElementById('pricing-policy');
            const coursePriceField = document.getElementById('course-price-field');
            pricingPolicyField.addEventListener('change', () => {
                coursePriceField.classList.toggle('hidden', pricingPolicyField.value === 'free');
            });

            const certPolicyField = document.getElementById('course-certificate-policy');
            const certPriceField = document.getElementById('course-certificate-price-field');
            certPolicyField.addEventListener('change', () => {
                certPriceField.classList.toggle('hidden', certPolicyField.value !== 'paid');
            });
        })();
    </script>
@endpush
