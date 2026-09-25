<?php

namespace Elibrary\Library\Payments;

use App\Models\Scopes\TenantScope;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaystackGateway implements PaymentGatewayContract
{
    private const BASE_URL = 'https://api.paystack.co';

    public function checkoutUrl(LibraryResourcePurchase $purchase, LibraryPaymentGatewayCredential $credential): string
    {
        $response = Http::withToken($credential->credentials['secret_key'] ?? '')
            ->post(self::BASE_URL.'/transaction/initialize', [
                'email' => $purchase->user->email,
                'amount' => (int) round($purchase->amount * 100),
                'currency' => $purchase->currency,
                'reference' => $purchase->reference,
                'callback_url' => route('library.resources.show', $purchase->resource),
            ])
            ->throw()
            ->json();

        return $response['data']['authorization_url'];
    }

    public function verifyWebhookSignature(Request $request, LibraryPaymentGatewayCredential $credential): bool
    {
        $secret = $credential->credentials['secret_key'] ?? null;

        if (! $secret) {
            return false;
        }

        $expected = hash_hmac('sha512', $request->getContent(), $secret);

        return hash_equals($expected, (string) $request->header('x-paystack-signature', ''));
    }

    public function resolvePurchaseFromWebhook(Request $request): ?LibraryResourcePurchase
    {
        $payload = json_decode($request->getContent(), true);
        $reference = $payload['data']['reference'] ?? null;

        if (! $reference) {
            return null;
        }

        return LibraryResourcePurchase::withoutGlobalScope(TenantScope::class)
            ->where('reference', $reference)
            ->first();
    }

    public function verifyByReference(string $reference, LibraryPaymentGatewayCredential $credential): array
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
