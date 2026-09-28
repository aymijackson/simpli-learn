<?php

namespace Elibrary\Library\Http\Controllers\Manage;

use App\Support\GatewayCredentials;
use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\ActivityLog;

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
            'clear_secrets' => ['nullable', 'boolean'],
        ]);

        // Secrets are never sent back to the form; a blank one keeps what's saved.
        $credential = LibraryPaymentGatewayCredential::firstOrNew(['tenant_id' => app(Tenancy::class)->id(), 'gateway' => $gatewayEnum->value]);
        $credential->fill([
            'is_enabled' => $request->boolean('is_enabled'),
            'credentials' => GatewayCredentials::merge($credential->credentials, $request),
        ])->save();

        ActivityLog::record('settings.gateway', 'Updated '.$gatewayEnum->label().' payment settings ('.($request->boolean('is_enabled') ? 'enabled' : 'disabled').')');

        return back()->with('status', $gatewayEnum->label().' settings updated.');
    }
}
