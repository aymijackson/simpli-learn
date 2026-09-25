<?php

namespace Elibrary\Library\Payments;

use App\Models\Scopes\TenantScope;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlutterwaveGateway implements PaymentGatewayContract
{
    private const BASE_URL = 'https://api.flutterwave.com/v3';

    public function checkoutUrl(LibraryResourcePurchase $purchase, LibraryPaymentGatewayCredential $credential): string
    {
        $response = Http::withToken($credential->credentials['secret_key'] ?? '')
            ->post(self::BASE_URL.'/payments', [
                'tx_ref' => $purchase->reference,
                'amount' => (string) $purchase->amount,
                'currency' => $purchase->currency,
                'redirect_url' => route('library.resources.show', $purchase->resource),
                'customer' => ['email' => $purchase->user->email],
            ])
            ->throw()
            ->json();

        return $response['data']['link'];
    }

    public function verifyWebhookSignature(Request $request, LibraryPaymentGatewayCredential $credential): bool
    {
        $secretHash = $credential->credentials['webhook_secret'] ?? null;

        if (! $secretHash) {
            return false;
        }

        return hash_equals($secretHash, (string) $request->header('verif-hash', ''));
    }

    public function resolvePurchaseFromWebhook(Request $request): ?LibraryResourcePurchase
    {
        $payload = json_decode($request->getContent(), true);
        $reference = $payload['data']['tx_ref'] ?? null;

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
            ->get(self::BASE_URL.'/transactions/verify_by_reference', ['tx_ref' => $reference])
            ->throw()
            ->json();

        return [
            'paid' => ($response['data']['status'] ?? null) === 'successful',
            'gateway_reference' => (string) ($response['data']['id'] ?? $reference),
        ];
    }
}
