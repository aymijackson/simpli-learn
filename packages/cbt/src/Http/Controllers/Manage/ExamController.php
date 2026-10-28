<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Enums\CertificatePolicy;
use Elibrary\Cbt\Enums\NavigationMode;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        return view('cbt::manage.exams.index', [
            'exams' => Exam::withCount('questions')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('cbt::manage.exams.create', ['exam' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $exam = Exam::create($this->validated($request));

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Exam created.');
    }

    public function edit(string $tenant, Exam $exam): View
    {
        return view('cbt::manage.exams.edit', [
            'exam' => $exam,
            'questions' => $exam->questions()->withCount('options')->get(),
            'sections' => $exam->sections,
        ]);
    }

    public function update(Request $request, string $tenant, Exam $exam): RedirectResponse
    {
        $exam->update($this->validated($request, $exam));

        return redirect()->route('cbt.manage.exams.edit', $exam)->with('status', 'Exam updated.');
    }

    public function destroy(string $tenant, Exam $exam): RedirectResponse
    {
        $exam->delete();

        return redirect()->route('cbt.manage.exams.index')->with('status', 'Exam deleted.');
    }

    private function validated(Request $request, ?Exam $exam = null): array
    {
        $tenantId = app(Tenancy::class)->id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'alpha_dash', 'max:255',
                Rule::unique('exams', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($exam),
            ],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'pass_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'navigation_mode' => ['nullable', Rule::in(array_column(NavigationMode::cases(), 'value'))],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'questions_per_attempt' => ['nullable', 'integer', 'min:1'],
            'certificate_policy' => ['nullable', Rule::in(array_column(CertificatePolicy::cases(), 'value'))],
            'certificate_price' => ['nullable', 'numeric', 'min:0'],
            'certificate_currency' => ['nullable', 'string', 'size:3'],
            'proctoring_level' => ['nullable', Rule::in(['off', 'browser', 'webcam'])],
            'snapshot_interval_seconds' => ['nullable', 'integer', 'min:15', 'max:300'],
            'proctoring_retention_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'invigilator_code' => ['nullable', 'string', 'max:50'],
            'allowed_networks' => ['nullable', 'string', 'max:2000', function ($attribute, $value, $fail) {
                foreach (array_filter(preg_split('/[\s,]+/', (string) $value)) as $network) {
                    if (! $this->isNetwork($network)) {
                        $fail("\"{$network}\" isn't an IP address or range (for example 102.89.34.10 or 102.89.34.0/24).");

                        return;
                    }
                }
            }],
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['enforce_time_limit'] = $request->boolean('enforce_time_limit');
        $level = $validated['proctoring_level'] ?? 'off';
        unset($validated['proctoring_level']);
        $validated['integrity_monitoring_enabled'] = $level !== 'off';
        $validated['webcam_proctoring'] = $level === 'webcam';
        $validated['snapshot_interval_seconds'] = $validated['snapshot_interval_seconds'] ?? $exam?->snapshot_interval_seconds ?? 60;
        $validated['proctoring_retention_days'] = $validated['proctoring_retention_days'] ?? $exam?->proctoring_retention_days ?? 90;
        $validated['invigilator_code'] = filled($validated['invigilator_code'] ?? null) ? trim($validated['invigilator_code']) : null;
        $validated['allowed_networks'] = filled($validated['allowed_networks'] ?? null)
            ? implode("\n", array_filter(preg_split('/[\s,]+/', $validated['allowed_networks'])))
            : null;
        $validated['navigation_mode'] = $validated['navigation_mode'] ?? NavigationMode::AllAtOnce->value;
        $validated['allow_backward_navigation'] = $validated['navigation_mode'] === NavigationMode::OneAtATime->value
            ? $request->boolean('allow_backward_navigation')
            : true;

        $validated['allow_retakes'] = $request->boolean('allow_retakes');
        $validated['max_attempts'] = $validated['allow_retakes'] ? $validated['max_attempts'] : null;
        $validated['randomize_questions'] = $request->boolean('randomize_questions');

        $validated['certificate_policy'] = $validated['certificate_policy'] ?? CertificatePolicy::Inherit->value;
        if (in_array($validated['certificate_policy'], [CertificatePolicy::Inherit->value, CertificatePolicy::Free->value], true)) {
            $validated['certificate_price'] = null;
            $validated['certificate_currency'] = null;
        }

        return $validated;
    }

    /** An IPv4/IPv6 address, optionally with a /prefix. */
    private function isNetwork(string $value): bool
    {
        [$address, $prefix] = array_pad(explode('/', $value, 2), 2, null);
        if (! filter_var($address, FILTER_VALIDATE_IP)) {
            return false;
        }
        if ($prefix === null) {
            return true;
        }
        $max = str_contains($address, ':') ? 128 : 32;

        return ctype_digit($prefix) && (int) $prefix <= $max;
    }
}
