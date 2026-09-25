<?php

namespace Elibrary\Cbt\Payments;

use App\Models\Scopes\TenantScope;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaystackGateway implements PaymentGatewayContract
{
    private const BASE_URL = 'https://api.paystack.co';

    public function checkoutUrl(CertificatePayment $payment, PaymentGatewayCredential $credential): string
    {
        $response = Http::withToken($credential->credentials['secret_key'] ?? '')
            ->post(self::BASE_URL.'/transaction/initialize', [
                'email' => $payment->user->email,
                'amount' => (int) round($payment->amount * 100),
                'currency' => $payment->currency,
                'reference' => $payment->reference,
                'callback_url' => route('cbt.attempts.result', $payment->exam_attempt_id),
            ])
            ->throw()
            ->json();

        return $response['data']['authorization_url'];
    }

    public function verifyWebhookSignature(Request $request, PaymentGatewayCredential $credential): bool
    {
        $secret = $credential->credentials['secret_key'] ?? null;

        if (! $secret) {
            return false;
        }

        $expected = hash_hmac('sha512', $request->getContent(), $secret);

        return hash_equals($expected, (string) $request->header('x-paystack-signature', ''));
    }

    public function resolvePaymentFromWebhook(Request $request): ?CertificatePayment
    {
        $payload = json_decode($request->getContent(), true);
        $reference = $payload['data']['reference'] ?? null;

        if (! $reference) {
            return null;
        }

        return CertificatePayment::withoutGlobalScope(TenantScope::class)
            ->where('reference', $reference)
            ->first();
    }

    public function verifyByReference(string $reference, PaymentGatewayCredential $credential): array
    {
        $response = Http::withToken($credential->credentials['secret_key'] ?? '')
            ->get(self::BASE_URL."/transaction/verify/{$reference}")
            ->throw()
            ->json();

        return [
            'paid' => ($response['data']['status'] ?? null) === 'success',
            'gateway_reference' => (string) ($response['data']['reference'] ?? $reference),
        ];
    }
}
