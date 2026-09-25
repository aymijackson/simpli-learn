<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use Elibrary\Cbt\Enums\PaymentCollector;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Elibrary\Cbt\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    /**
     * One tenant-agnostic endpoint per gateway — a tenant's own Stripe/
     * Paystack/Flutterwave dashboard has a single fixed callback URL with no
     * room for a per-tenant path segment, so the payment's own `reference`
     * (embedded at checkout-creation time) is how this is matched back to a
     * tenant, not the URL. Unauthenticated by design; protected by
     * per-gateway signature verification instead of auth/CSRF.
     */
    public function handle(Request $request, string $gateway): Response
    {
        $gatewayEnum = PaymentGateway::tryFrom($gateway);
        abort_unless($gatewayEnum, 404);

        $service = app(PaymentGatewayFactory::class)->make($gatewayEnum);
        abort_unless($service, 404);

        $payment = $service->resolvePaymentFromWebhook($request);
        abort_unless($payment, 404);

        $credential = PaymentGatewayCredential::withoutGlobalScope(TenantScope::class)
            ->where('gateway', $gatewayEnum->value)
            ->where(function ($query) use ($payment) {
                $payment->collected_by === PaymentCollector::Platform
                    ? $query->whereNull('tenant_id')
                    : $query->where('tenant_id', $payment->tenant_id);
            })
            ->first();

        abort_unless($credential && $service->verifyWebhookSignature($request, $credential), 403);

        if ($payment->isPending()) {
            $result = $service->verifyByReference($payment->reference, $credential);

            if ($result['paid']) {
                $payment->markPaidAndIssueCertificate(null, $result['gateway_reference']);
            }
        }

        return response()->noContent();
    }
}
