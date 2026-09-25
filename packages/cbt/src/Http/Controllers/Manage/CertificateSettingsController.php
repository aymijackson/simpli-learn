<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Enums\CertificatePolicy;
use Elibrary\Cbt\Models\TenantCertificateSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CertificateSettingsController extends Controller
{
    public function edit(): View
    {
        return view('cbt::manage.certificates.settings', [
            'settings' => $this->settings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_policy' => ['required', Rule::in(['none', 'free', 'freemium', 'paid'])],
            'default_price' => ['nullable', 'numeric', 'min:0'],
            'default_currency' => ['required', 'string', 'size:3'],
            'bank_transfer_instructions' => ['nullable', 'string'],
            'issuer_name' => ['nullable', 'string', 'max:255'],
            'signatory_name' => ['nullable', 'string', 'max:255'],
            'signatory_title' => ['nullable', 'string', 'max:255'],
        ]);

        $this->settings()->update($validated);

        return back()->with('status', 'Certificate settings updated.');
    }

    private function settings(): TenantCertificateSettings
    {
        return TenantCertificateSettings::firstOrCreate(
            ['tenant_id' => app(Tenancy::class)->id()],
            ['default_policy' => 'free', 'default_currency' => 'USD']
        );
    }
}
