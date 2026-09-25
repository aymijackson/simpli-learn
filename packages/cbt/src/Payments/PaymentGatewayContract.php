<?php

namespace Elibrary\Cbt\Payments;

use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Illuminate\Http\Request;

/**
 * Implemented by the redirect-checkout gateways (Stripe, Paystack,
 * Flutterwave). Bank transfer is fundamentally different — no processor,
 * no webhook, self-reported by the learner and manually confirmed by the
 * owner — so it's handled directly by CertificatePaymentController instead
 * of through this contract.
 */
interface PaymentGatewayContract
{
    public function checkoutUrl(CertificatePayment $payment, PaymentGatewayCredential $credential): string;

    public function verifyWebhookSignature(Request $request, PaymentGatewayCredential $credential): bool;

    public function resolvePaymentFromWebhook(Request $request): ?CertificatePayment;

    /** @return array{paid: bool, gateway_reference: ?string} */
    public function verifyByReference(string $reference, PaymentGatewayCredential $credential): array;
}
