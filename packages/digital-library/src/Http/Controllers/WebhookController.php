<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    /**
     * One tenant-agnostic endpoint per gateway — mirrors Elibrary\Lms's
     * WebhookController. The purchase's own `reference` is how this is
     * matched back to a tenant, not the URL.
     */
    public function handle(Request $request, string $gateway): Response
    {
        $gatewayEnum = LibraryPaymentGateway::tryFrom($gateway);
        abort_unless($gatewayEnum, 404);

        $service = app(PaymentGatewayFactory::class)->make($gatewayEnum);
        abort_unless($service, 404);

        $purchase = $service->resolvePurchaseFromWebhook($request);
        abort_unless($purchase, 404);

        $credential = LibraryPaymentGatewayCredential::withoutGlobalScope(TenantScope::class)
            ->where('gateway', $gatewayEnum->value)
            ->where('tenant_id', $purchase->tenant_id)
            ->first();

        abort_unless($credential && $service->verifyWebhookSignature($request, $credential), 403);

        if ($purchase->isPending()) {
            $result = $service->verifyByReference($purchase->reference, $credential);

            if ($result['paid']) {
                $purchase->markPaid(null, $result['gateway_reference']);
            }
        }

        return response()->noContent();
    }
}
