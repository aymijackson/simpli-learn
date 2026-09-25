<?php

namespace Elibrary\Cbt\Payments;

use App\Models\Scopes\TenantScope;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlutterwaveGateway implements PaymentGatewayContract
{
    private const BASE_URL = 'https://api.flutterwave.com/v3';

    public function checkoutUrl(CertificatePayment $payment, PaymentGatewayCredential $credential): string
    {
        $response = Http::withToken($credential->credentials['secret_key'] ?? '')
            ->post(self::BASE_URL.'/payments', [
                'tx_ref' => $payment->reference,
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'redirect_url' => route('cbt.attempts.result', $payment->exam_attempt_id),
                'customer' => ['email' => $payment->user->email],
            ])
            ->throw()
            ->json();

        return $response['data']['link'];
    }

    /**
     * Flutterwave's webhook auth isn't HMAC — it's a static secret hash you
     * configure in the dashboard, echoed back verbatim in the `verif-hash`
     * header, checked with a plain constant-time string comparison.
     */
    public function verifyWebhookSignature(Request $request, PaymentGatewayCredential $credential): bool
    {
        $secretHash = $credential->credentials['webhook_secret'] ?? null;

        if (! $secretHash) {
            return false;
        }

        return hash_equals($secretHash, (string) $request->header('verif-hash', ''));
    }

    public function resolvePaymentFromWebhook(Request $request): ?CertificatePayment
    {
        $payload = json_decode($request->getContent(), true);
        $reference = $payload['data']['tx_ref'] ?? null;

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
            ->get(self::BASE_URL.'/transactions/verify_by_reference', ['tx_ref' => $reference])
            ->throw()
            ->json();

        return [
            'paid' => ($response['data']['status'] ?? null) === 'successful',
            'gateway_reference' => (string) ($response['data']['id'] ?? $reference),
        ];
    }
}
