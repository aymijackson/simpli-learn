<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Enums\LibraryPurchaseStatus;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Elibrary\Library\Payments\PaymentGatewayFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LibraryResourcePurchaseController extends Controller
{
    public function create(Request $request, string $tenant, LibraryResource $resource): View
    {
        abort_if($resource->isPurchasedBy($request->user()), 404);

        $tenantId = app(Tenancy::class)->id();
        $gateways = collect(LibraryPaymentGateway::cases())->filter(
            fn (LibraryPaymentGateway $gateway) => LibraryPaymentGatewayCredential::where('tenant_id', $tenantId)
                ->where('gateway', $gateway->value)
                ->where('is_enabled', true)
                ->exists()
        )->values();

        return view('library::resources.purchase', [
            'resource' => $resource,
            'gateways' => $gateways,
        ]);
    }

    public function store(Request $request, string $tenant, LibraryResource $resource): RedirectResponse
    {
        abort_if($resource->isPurchasedBy($request->user()), 404);

        $validated = $request->validate([
            'gateway' => ['required', Rule::in(array_column(LibraryPaymentGateway::cases(), 'value'))],
        ]);

        $gateway = LibraryPaymentGateway::from($validated['gateway']);
        $tenantId = app(Tenancy::class)->id();
        $credential = LibraryPaymentGatewayCredential::where('tenant_id', $tenantId)
            ->where('gateway', $gateway->value)
            ->first();

        abort_unless($credential && $credential->is_enabled, 404);

        $purchase = LibraryResourcePurchase::create([
            'resource_id' => $resource->id,
            'user_id' => $request->user()->id,
            'gateway' => $gateway->value,
            'amount' => $resource->price,
            'currency' => $resource->currency,
            'status' => LibraryPurchaseStatus::Pending->value,
            'reference' => (string) Str::uuid(),
        ]);

        if ($gateway === LibraryPaymentGateway::BankTransfer) {
            return redirect()->route('library.resources.purchase.bank-transfer.show', [$resource, $purchase]);
        }

        $gatewayService = app(PaymentGatewayFactory::class)->make($gateway);
        abort_unless($gatewayService, 404);

        return redirect()->away($gatewayService->checkoutUrl($purchase, $credential));
    }

    public function bankTransferShow(Request $request, string $tenant, LibraryResource $resource, LibraryResourcePurchase $purchase): View
    {
        abort_unless($purchase->resource_id === $resource->id && $purchase->user_id === $request->user()->id, 404);
        abort_unless($purchase->gateway === LibraryPaymentGateway::BankTransfer, 404);

        $tenantId = app(Tenancy::class)->id();
        $bankCredential = LibraryPaymentGatewayCredential::where('tenant_id', $tenantId)
            ->where('gateway', LibraryPaymentGateway::BankTransfer->value)
            ->first();

        return view('library::resources.bank-transfer', [
            'resource' => $resource,
            'purchase' => $purchase,
            'bankInstructions' => $bankCredential?->credentials['public_key'] ?? null,
        ]);
    }

    public function bankTransferReport(Request $request, string $tenant, LibraryResource $resource, LibraryResourcePurchase $purchase): RedirectResponse
    {
        abort_unless($purchase->resource_id === $resource->id && $purchase->user_id === $request->user()->id, 404);
        abort_unless($purchase->gateway === LibraryPaymentGateway::BankTransfer, 404);
        abort_unless($purchase->isPending(), 404);

        $purchase->update(['metadata' => array_merge($purchase->metadata ?? [], ['reported_paid_at' => now()->toIso8601String()])]);

        return redirect()
            ->route('library.resources.show', $resource)
            ->with('status', "Thanks — we've recorded your payment claim. The organization will confirm it shortly.");
    }
}
