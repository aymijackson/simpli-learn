<?php

namespace Elibrary\Cbt\Payments;

use App\Models\Scopes\TenantScope;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements PaymentGatewayContract
{
    public function checkoutUrl(CertificatePayment $payment, PaymentGatewayCredential $credential): string
    {
        $client = new StripeClient($credential->credentials['secret_key'] ?? '');

        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => $payment->reference,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'unit_amount' => (int) round($payment->amount * 100),
                    'product_data' => ['name' => 'Verified certificate'],
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('cbt.attempts.result', $payment->exam_attempt_id).'?paid=1',
            'cancel_url' => route('cbt.attempts.result', $payment->exam_attempt_id),
        ]);

        $payment->update(['gateway_reference' => $session->id]);

        return $session->url;
    }

    public function verifyWebhookSignature(Request $request, PaymentGatewayCredential $credential): bool
    {
        $secret = $credential->credentials['webhook_secret'] ?? null;

        if (! $secret) {
            return false;
        }

        try {
            Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature', ''), $secret);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function resolvePaymentFromWebhook(Request $request): ?CertificatePayment
    {
        $payload = json_decode($request->getContent(), true);
        $reference = $payload['data']['object']['client_reference_id'] ?? null;

        if (! $reference) {
            return null;
        }

        return CertificatePayment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('reference', $reference)
            ->first();
    }

    public function verifyByReference(string $reference, PaymentGatewayCredential $credential): array
    {
        $payment = CertificatePayment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('reference', $reference)
            ->first();

        if (! $payment || ! $payment->gateway_reference) {
            return ['paid' => false, 'gateway_reference' => null];
        }

        $client = new StripeClient($credential->credentials['secret_key'] ?? '');
        $session = $client->checkout->sessions->retrieve($payment->gateway_reference);

        return [
            'paid' => $session->payment_status === 'paid',
            'gateway_reference' => $session->id,
        ];
    }
}
