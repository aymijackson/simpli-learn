<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Enums\CoursePaymentGateway;
use Elibrary\Lms\Models\CoursePaymentGatewayCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\ActivityLog;

class CoursePaymentGatewayController extends Controller
{
    public function edit(): View
    {
        $tenantId = app(Tenancy::class)->id();

        $credentials = [];
        foreach (CoursePaymentGateway::cases() as $gateway) {
            $credentials[$gateway->value] = CoursePaymentGatewayCredential::where('tenant_id', $tenantId)
                ->where('gateway', $gateway->value)
                ->first();
        }

        return view('lms::manage.payment-gateways.edit', [
            'gateways' => CoursePaymentGateway::cases(),
            'credentials' => $credentials,
        ]);
    }

    public function update(Request $request, string $tenant, string $gateway): RedirectResponse
    {
        $gatewayEnum = CoursePaymentGateway::tryFrom($gateway);
        abort_unless($gatewayEnum, 404);

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'public_key' => ['nullable', 'string'],
            'secret_key' => ['nullable', 'string'],
            'webhook_secret' => ['nullable', 'string'],
        ]);

        CoursePaymentGatewayCredential::updateOrCreate(
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

        ActivityLog::record('settings.gateway', 'Updated '.$gatewayEnum->label().' payment settings ('.($request->boolean('is_enabled') ? 'enabled' : 'disabled').')');

        return back()->with('status', $gatewayEnum->label().' settings updated.');
    }
}
