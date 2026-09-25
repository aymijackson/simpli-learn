<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryPaymentGatewayController extends Controller
{
    public function edit(): View
    {
        $tenantId = app(Tenancy::class)->id();

        $credentials = [];
        foreach (LibraryPaymentGateway::cases() as $gateway) {
            $credentials[$gateway->value] = LibraryPaymentGatewayCredential::where('tenant_id', $tenantId)
                ->where('gateway', $gateway->value)
                ->first();
        }

        return view('library::manage.payment-gateways.edit', [
            'gateways' => LibraryPaymentGateway::cases(),
            'credentials' => $credentials,
        ]);
    }

    public function update(Request $request, string $tenant, string $gateway): RedirectResponse
    {
        $gatewayEnum = LibraryPaymentGateway::tryFrom($gateway);
        abort_unless($gatewayEnum, 404);

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'public_key' => ['nullable', 'string'],
            'secret_key' => ['nullable', 'string'],
            'webhook_secret' => ['nullable', 'string'],
        ]);

        LibraryPaymentGatewayCredential::updateOrCreate(
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
