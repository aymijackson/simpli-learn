<?php

namespace Elibrary\Library\Payments;

use App\Models\Scopes\TenantScope;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Support\Facades\Log;

/**
 * The one place a card payment is confirmed: asks the gateway directly
 * (never trusting what the browser or webhook body says), then checks the
 * gateway charged the purchase's own amount and currency before marking it
 * paid. Used by both the webhook and the learner's return from checkout,
 * so access doesn't depend on the webhook arriving.
 */
class LibraryPaymentConfirmation
{
    public function __construct(private PaymentGatewayFactory $gateways)
    {
    }

    /** True when the purchase is (now) paid. */
    public function confirm(LibraryResourcePurchase $purchase): bool
    {
        if (! $purchase->isPending()) {
            return $purchase->status->value === 'paid';
        }

        if ($purchase->gateway === LibraryPaymentGateway::BankTransfer) {
            return false; // confirmed by the owner, by hand
        }

        $service = $this->gateways->make($purchase->gateway);
        $credential = LibraryPaymentGatewayCredential::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $purchase->tenant_id)
            ->where('gateway', $purchase->gateway->value)
            ->first();

        if (! $service || ! $credential) {
            return false;
        }

        $result = $service->verifyByReference($purchase->reference, $credential);

        if (! $result['paid']) {
            return false;
        }

        if (! $this->matchesPrice($purchase, $result)) {
            Log::warning('Library payment amount/currency mismatch — not granting access.', [
                'purchase' => $purchase->id,
                'expected' => [$purchase->amount, $purchase->currency],
                'charged' => [$result['amount'] ?? null, $result['currency'] ?? null],
            ]);

            return false;
        }

        $purchase->markPaid(null, $result['gateway_reference']);

        return true;
    }

    private function matchesPrice(LibraryResourcePurchase $purchase, array $result): bool
    {
        if (! isset($result['amount'], $result['currency'])) {
            return false;
        }

        return strcasecmp((string) $result['currency'], (string) $purchase->currency) === 0
            && (int) round((float) $result['amount'] * 100) >= (int) round((float) $purchase->amount * 100);
    }
}
