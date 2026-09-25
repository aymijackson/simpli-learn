<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlatformPaymentMode;
use App\Http\Controllers\Controller;
use App\Models\PlatformPaymentSettings;
use App\Models\Tenant;
use App\Models\TenantRevenueSplit;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    public function edit(): View
    {
        $credentials = [];
        foreach (PaymentGateway::cases() as $gateway) {
            $credentials[$gateway->value] = PaymentGatewayCredential::whereNull('tenant_id')
                ->where('gateway', $gateway->value)
                ->first();
        }

        return view('admin.payment-settings.edit', [
            'settings' => PlatformPaymentSettings::current(),
            'modes' => PlatformPaymentMode::cases(),
            'gateways' => PaymentGateway::cases(),
            'credentials' => $credentials,
            'tenants' => Tenant::with('revenueSplit')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(array_column(PlatformPaymentMode::cases(), 'value'))],
        ]);

        PlatformPaymentSettings::current()->update($validated);

        return back()->with('status', 'Platform payment mode updated.');
    }

    public function updateGateway(Request $request, string $gateway): RedirectResponse
    {
        $gatewayEnum = PaymentGateway::tryFrom($gateway);
        abort_unless($gatewayEnum, 404);

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'public_key' => ['nullable', 'string'],
            'secret_key' => ['nullable', 'string'],
            'webhook_secret' => ['nullable', 'string'],
        ]);

        PaymentGatewayCredential::updateOrCreate(
            ['tenant_id' => null, 'gateway' => $gatewayEnum->value],
            [
                'is_enabled' => $request->boolean('is_enabled'),
                'credentials' => array_filter([
                    'public_key' => $validated['public_key'] ?? null,
                    'secret_key' => $validated['secret_key'] ?? null,
                    'webhook_secret' => $validated['webhook_secret'] ?? null,
                ]),
            ]
        );

        return back()->with('status', 'Platform '.$gatewayEnum->label().' settings updated.');
    }

    public function updateRevenueSplit(Request $request, Tenant $workspace): RedirectResponse
    {
        $validated = $request->validate([
            'split_type' => ['required', Rule::in(['manual', 'percentage', 'flat_fee'])],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'flat_fee' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        TenantRevenueSplit::updateOrCreate(['tenant_id' => $workspace->id], $validated);

        return back()->with('status', 'Revenue split updated for '.$workspace->name.'.');
    }
}
