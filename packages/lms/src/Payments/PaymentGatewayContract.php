<?php

namespace Elibrary\Lms\Payments;

use Elibrary\Lms\Models\CoursePaymentGatewayCredential;
use Elibrary\Lms\Models\CoursePurchase;
use Illuminate\Http\Request;

/**
 * Implemented by the redirect-checkout gateways (Stripe, Paystack,
 * Flutterwave). Bank transfer is handled directly by CoursePurchaseController
 * instead — no processor to redirect through, self-reported by the learner
 * and manually confirmed by the owner. Mirrors Elibrary\Cbt\Payments's
 * contract of the same shape, kept as a separate implementation per the
 * "keep course payments independent of certificate payments" decision.
 */
interface PaymentGatewayContract
{
    public function checkoutUrl(CoursePurchase $purchase, CoursePaymentGatewayCredential $credential): string;

    public function verifyWebhookSignature(Request $request, CoursePaymentGatewayCredential $credential): bool;

    public function resolvePurchaseFromWebhook(Request $request): ?CoursePurchase;

    /** @return array{paid: bool, gateway_reference: ?string} */
    public function verifyByReference(string $reference, CoursePaymentGatewayCredential $credential): array;
}
