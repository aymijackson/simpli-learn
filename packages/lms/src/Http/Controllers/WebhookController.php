<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use Elibrary\Lms\Enums\CoursePaymentGateway;
use Elibrary\Lms\Models\CoursePaymentGatewayCredential;
use Elibrary\Lms\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    /**
     * One tenant-agnostic endpoint per gateway — mirrors Elibrary\Cbt's
     * WebhookController. The purchase's own `reference` (embedded at
     * checkout-creation time) is how this is matched back to a tenant, not
     * the URL. Unauthenticated by design; protected by per-gateway signature
     * verification instead of auth/CSRF.
     */
    public function handle(Request $request, string $gateway): Response
    {
        $gatewayEnum = CoursePaymentGateway::tryFrom($gateway);
        abort_unless($gatewayEnum, 404);

        $service = app(PaymentGatewayFactory::class)->make($gatewayEnum);
        abort_unless($service, 404);

        $purchase = $service->resolvePurchaseFromWebhook($request);
        abort_unless($purchase, 404);

        $credential = CoursePaymentGatewayCredential::withoutGlobalScope(TenantScope::class)
            ->where('gateway', $gatewayEnum->value)
            ->where('tenant_id', $purchase->tenant_id)
            ->first();

        abort_unless($credential && $service->verifyWebhookSignature($request, $credential), 403);

        if ($purchase->isPending()) {
            $result = $service->verifyByReference($purchase->reference, $credential);

            if ($result['paid']) {
                $purchase->markPaidAndActivate(null, $result['gateway_reference']);
            }
        }

        return response()->noContent();
    }
}
