<x-input type="text" name="title" id="exam-title" label="Title" value="{{ old('title', $exam->title ?? '') }}" required autofocus />

<x-input type="text" name="slug" id="exam-slug" label="Slug" value="{{ old('slug', $exam->slug ?? '') }}" required />

<x-editor name="description" label="Description (optional)" :value="old('description', $exam->description ?? '')" />

<x-editor name="instructions" label="Instructions (optional)" :value="old('instructions', $exam->instructions ?? '')" />

<div class="grid gap-5 sm:grid-cols-2">
    <x-input type="number" name="duration_minutes" label="Duration (minutes)" value="{{ old('duration_minutes', $exam->duration_minutes ?? 30) }}" min="1" required />
    <x-input type="number" name="pass_percentage" label="Pass mark (%)" value="{{ old('pass_percentage', $exam->pass_percentage ?? 50) }}" min="0" max="100" required />
</div>

<label class="flex items-center gap-2 text-sm text-slate-600">
    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_published', $exam->is_published ?? false))>
    Published (visible to learners)
</label>

<div>
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="enforce_time_limit" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('enforce_time_limit', $exam->enforce_time_limit ?? true))>
        Enforce time limit
    </label>
    <p class="mt-1 text-xs text-slate-500">When enabled, a submission received after the duration has elapsed is rejected and the attempt is forfeited (scored 0). When disabled, the countdown is shown to learners but a late submission is still graded normally.</p>
</div>

@php($currentProctoring = old('proctoring_level', $exam?->proctoringLevel() ?? 'off'))

<fieldset class="rounded-xl p-4 ring-1 ring-slate-200">
    <legend class="px-1 text-sm font-medium text-slate-700">Proctoring</legend>
    <div class="space-y-3">
        @foreach ([
            'off' => ['Off', 'No monitoring.'],
            'browser' => ['Browser monitoring', 'Logs when a candidate leaves the exam tab or window, copies or pastes — timestamped for your review.'],
            'webcam' => ['Webcam snapshots', 'Browser monitoring, plus an ID check photo before the exam starts and webcam photos during it. Candidates must agree to it and have a working camera; the exam asks them to stay in full screen.'],
        ] as $value => [$label, $hint])
            <label class="flex gap-3">
                <input type="radio" name="proctoring_level" value="{{ $value }}" data-proctoring-level class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-600" @checked($currentProctoring === $value)>
                <span>
                    <span class="block text-sm text-slate-700">{{ $label }}</span>
                    <span class="block text-xs text-slate-500">{{ $hint }}</span>
                </span>
            </label>
        @endforeach
    </div>
    <p class="mt-3 text-xs text-slate-500">Monitoring never fails anyone by itself (with webcam snapshots, the exam does pause while the camera is off). Flagged attempts appear in Analytics for you to review and clear or confirm.</p>

    <div id="webcam-settings" class="mt-4 grid gap-5 sm:grid-cols-2 {{ $currentProctoring === 'webcam' ? '' : 'hidden' }}">
        <div>
            <x-input type="number" name="snapshot_interval_seconds" label="Seconds between photos" value="{{ old('snapshot_interval_seconds', $exam->snapshot_interval_seconds ?? 60) }}" min="15" max="300" />
            <p class="mt-1 text-xs text-slate-500">15–300. Extra photos are taken whenever something is flagged.</p>
        </div>
        <div>
            <x-input type="number" name="proctoring_retention_days" label="Keep photos for (days)" value="{{ old('proctoring_retention_days', $exam->proctoring_retention_days ?? 90) }}" min="1" max="730" />
            <p class="mt-1 text-xs text-slate-500">Counted from submission; then they are deleted automatically.</p>
        </div>
    </div>
</fieldset>

<fieldset class="rounded-xl p-4 ring-1 ring-slate-200">
    <legend class="px-1 text-sm font-medium text-slate-700">Supervised sitting (optional)</legend>
    <div class="space-y-5">
        <div>
            <x-input type="text" name="invigilator_code" label="Invigilator code" value="{{ old('invigilator_code', $exam?->invigilator_code ?? '') }}" maxlength="50" autocomplete="off" />
            <p class="mt-1 text-xs text-slate-500">If set, candidates need this code to start — give it out in the exam room. Change it for each sitting. Leave blank for no code.</p>
        </div>
        <div>
            <x-textarea name="allowed_networks" label="Allowed networks" rows="3" placeholder="e.g. 102.89.34.10 or 102.89.34.0/24">{{ old('allowed_networks', $exam?->allowed_networks ?? '') }}</x-textarea>
            <p class="mt-1 text-xs text-slate-500">IP addresses or ranges, one per line. If set, the exam can only be started and taken from these networks — for example your office or a test centre. Leave blank to allow anywhere.</p>
            <p class="mt-1 text-xs text-slate-500">Your current address is <span class="font-mono">{{ request()->ip() }}</span>.</p>
        </div>
    </div>
</fieldset>

@php($currentNavMode = old('navigation_mode', $exam->navigation_mode?->value ?? 'all_at_once'))

<div>
    <label for="navigation-mode" class="mb-1.5 block text-sm font-medium text-slate-700">Question navigation</label>
    <select id="navigation-mode" name="navigation_mode" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @foreach (\Elibrary\Cbt\Enums\NavigationMode::cases() as $mode)
            <option value="{{ $mode->value }}" @selected($currentNavMode === $mode->value)>{{ $mode->label() }}</option>
        @endforeach
    </select>
    <ul class="mt-1 space-y-0.5 text-xs text-slate-500">
        @foreach (\Elibrary\Cbt\Enums\NavigationMode::cases() as $mode)
            <li><span class="font-medium text-slate-600">{{ $mode->label() }}:</span> {{ $mode->description() }}</li>
        @endforeach
    </ul>
</div>

<div id="backward-nav-field" class="{{ $currentNavMode === 'one_at_a_time' ? '' : 'hidden' }}">
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="allow_backward_navigation" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('allow_backward_navigation', $exam->allow_backward_navigation ?? true))>
        Allow learners to navigate back to earlier questions
    </label>
    <p class="mt-1 text-xs text-slate-500">When off, once a learner moves past a question it can no longer be revisited or changed — enforced on the server, not just hidden in the UI.</p>
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Opens (optional)</label>
        <input aria-label="Opens (optional)" type="datetime-local" name="available_from" value="{{ old('available_from', $exam?->available_from?->format('Y-m-d\TH:i') ?? '') }}" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @error('available_from')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Closes (optional)</label>
        <input aria-label="Closes (optional)" type="datetime-local" name="available_until" value="{{ old('available_until', $exam?->available_until?->format('Y-m-d\TH:i') ?? '') }}" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @error('available_until')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
<p class="-mt-3 text-xs text-slate-500">Learners can't start a new attempt outside this window. An attempt already in progress is unaffected once started.</p>

<div>
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input id="allow-retakes" type="checkbox" name="allow_retakes" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('allow_retakes', $exam->allow_retakes ?? true))>
        Allow retakes
    </label>
</div>

<div id="max-attempts-field" class="{{ old('allow_retakes', $exam->allow_retakes ?? true) ? '' : 'hidden' }}">
    <x-input type="number" name="max_attempts" label="Max attempts per learner (optional)" value="{{ old('max_attempts', $exam->max_attempts ?? '') }}" min="1" />
    <p class="mt-1 text-xs text-slate-500">Leave blank for unlimited retakes.</p>
</div>

<div>
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="randomize_questions" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('randomize_questions', $exam->randomize_questions ?? false))>
        Randomize question order per attempt
    </label>
</div>

<div>
    <x-input type="number" name="questions_per_attempt" label="Questions per attempt (optional)" value="{{ old('questions_per_attempt', $exam->questions_per_attempt ?? '') }}" min="1" />
    <p class="mt-1 text-xs text-slate-500">Leave blank to show every question. When set, each attempt draws a random subset of this size from the full question pool.</p>
</div>

@php($currentCertPolicy = old('certificate_policy', $exam?->certificate_policy ?? 'inherit'))

<div>
    <label for="certificate-policy" class="mb-1.5 block text-sm font-medium text-slate-700">Certificate policy</label>
    <select id="certificate-policy" name="certificate_policy" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @foreach (\Elibrary\Cbt\Enums\CertificatePolicy::cases() as $policy)
            <option value="{{ $policy->value }}" @selected($currentCertPolicy === $policy->value)>{{ $policy->label() }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Overrides your organization's <a href="{{ route('cbt.manage.certificates.settings.edit') }}" class="underline">default certificate settings</a> for this exam only.</p>
</div>

<div id="certificate-price-field" class="grid gap-5 sm:grid-cols-2 {{ in_array($currentCertPolicy, ['inherit', 'free', 'none'], true) ? 'hidden' : '' }}">
    <x-input type="number" name="certificate_price" label="Certificate price" value="{{ old('certificate_price', $exam->certificate_price ?? '') }}" min="0" step="0.01" />
    <x-input type="text" name="certificate_currency" label="Currency (3-letter code)" value="{{ old('certificate_currency', $exam->certificate_currency ?? '') }}" maxlength="3" />
</div>

@push('scripts')
    <script>
        (function () {
            const titleField = document.getElementById('exam-title');
            const slugField = document.getElementById('exam-slug');
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

            const navModeField = document.getElementById('navigation-mode');
            const backwardNavField = document.getElementById('backward-nav-field');
            navModeField.addEventListener('change', () => {
                backwardNavField.classList.toggle('hidden', navModeField.value !== 'one_at_a_time');
            });

            const allowRetakesField = document.getElementById('allow-retakes');
            const maxAttemptsField = document.getElementById('max-attempts-field');
            allowRetakesField.addEventListener('change', () => {
                maxAttemptsField.classList.toggle('hidden', !allowRetakesField.checked);
            });

            const webcamSettings = document.getElementById('webcam-settings');
            document.querySelectorAll('[data-proctoring-level]').forEach((radio) => {
                radio.addEventListener('change', () => {
                    webcamSettings.classList.toggle('hidden', document.querySelector('[data-proctoring-level]:checked')?.value !== 'webcam');
                });
            });

            const certPolicyField = document.getElementById('certificate-policy');
            const certPriceField = document.getElementById('certificate-price-field');
            certPolicyField.addEventListener('change', () => {
                certPriceField.classList.toggle('hidden', ['inherit', 'free', 'none'].includes(certPolicyField.value));
            });
        })();
    </script>
@endpush
