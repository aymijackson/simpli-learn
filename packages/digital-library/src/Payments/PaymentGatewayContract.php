<?php

namespace Elibrary\Library\Payments;

use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Http\Request;

/**
 * Implemented by the redirect-checkout gateways (Stripe, Paystack,
 * Flutterwave). Bank transfer is handled directly by
 * LibraryResourcePurchaseController instead. Mirrors Elibrary\Lms\Payments's
 * contract of the same shape, kept as a fully separate implementation.
 */
interface PaymentGatewayContract
{
    public function checkoutUrl(LibraryResourcePurchase $purchase, LibraryPaymentGatewayCredential $credential): string;

    public function verifyWebhookSignature(Request $request, LibraryPaymentGatewayCredential $credential): bool;

    public function resolvePurchaseFromWebhook(Request $request): ?LibraryResourcePurchase;

    /**
     * What the gateway itself says was charged. `amount` is in major units
     * (e.g. 10.50), so LibraryPaymentConfirmation can check it matches.
     *
     * @return array{paid: bool, gateway_reference: ?string, amount?: float|null, currency?: string|null}
     */
    public function verifyByReference(string $reference, LibraryPaymentGatewayCredential $credential): array;
}
