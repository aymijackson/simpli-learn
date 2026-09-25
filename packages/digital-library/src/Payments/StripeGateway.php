<?php

namespace Elibrary\Library\Payments;

use App\Models\Scopes\TenantScope;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements PaymentGatewayContract
{
    public function checkoutUrl(LibraryResourcePurchase $purchase, LibraryPaymentGatewayCredential $credential): string
    {
        $client = new StripeClient($credential->credentials['secret_key'] ?? '');

        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => $purchase->reference,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($purchase->currency),
                    'unit_amount' => (int) round($purchase->amount * 100),
                    'product_data' => ['name' => $purchase->resource->title],
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('library.resources.show', $purchase->resource).'?paid=1',
            'cancel_url' => route('library.resources.show', $purchase->resource),
        ]);

        $purchase->update(['gateway_reference' => $session->id]);

        return $session->url;
    }

    public function verifyWebhookSignature(Request $request, LibraryPaymentGatewayCredential $credential): bool
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

    public function resolvePurchaseFromWebhook(Request $request): ?LibraryResourcePurchase
    {
        $payload = json_decode($request->getContent(), true);
        $reference = $payload['data']['object']['client_reference_id'] ?? null;

        if (! $reference) {
            return null;
        }

        return LibraryResourcePurchase::withoutGlobalScope(TenantScope::class)
            ->where('reference', $reference)
            ->first();
    }

    public function verifyByReference(string $reference, LibraryPaymentGatewayCredential $credential): array
    {
        $purchase = LibraryResourcePurchase::withoutGlobalScope(TenantScope::class)
            ->where('reference', $reference)
            ->first();

        if (! $purchase || ! $purchase->gateway_reference) {
            return ['paid' => false, 'gateway_reference' => null];
        }

        $client = new StripeClient($credential->credentials['secret_key'] ?? '');
        $session = $client->checkout->sessions->retrieve($purchase->gateway_reference);

        return [
            'paid' => $session->payment_status === 'paid',
            'gateway_reference' => $session->id,
        ];
    }
}
