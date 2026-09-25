<?php

namespace Elibrary\Cbt\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\PlatformPaymentSettings;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function edit(): View
    {
        $tenantId = app(Tenancy::class)->id();

        $credentials = [];
        foreach (PaymentGateway::cases() as $gateway) {
            $credentials[$gateway->value] = PaymentGatewayCredential::where('tenant_id', $tenantId)
                ->where('gateway', $gateway->value)
                ->first();
        }

        return view('cbt::manage.payment-gateways.edit', [
            'gateways' => PaymentGateway::cases(),
            'credentials' => $credentials,
            'platformManaged' => PlatformPaymentSettings::current()->isPlatformManaged(),
        ]);
    }

    public function update(Request $request, string $tenant, string $gateway): RedirectResponse
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
            ['tenant_id' => app(Tenancy::class)->id(), 'gateway' => $gatewayEnum->value],
            [
                'is_enabled' => $request->boolean('is_enabled'),
                'credentials' => array_filter([
                    'public_key' => $validated['public_key'] ?? null,
                    'secret_key' => $validated['secret_key'] ?? null,
                    'webhook_secret' => $validated['webhook_secret'] ?? null,
                ]),
            ]
        );

        return back()->with('status', $gatewayEnum->label().' settings updated.');
    }
}
