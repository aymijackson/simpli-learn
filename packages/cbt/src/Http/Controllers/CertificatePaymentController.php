<?php

namespace Elibrary\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PlatformPaymentSettings;
use App\Models\TenantRevenueSplit;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Enums\CertificateTier;
use Elibrary\Cbt\Enums\PaymentCollector;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Enums\PaymentStatus;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Payments\GatewayCredentialResolver;
use Elibrary\Cbt\Payments\PaymentGatewayFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CertificatePaymentController extends Controller
{
    public function create(Request $request, string $tenant, ExamAttempt $attempt): View
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        $this->assertPurchasable($attempt);

        $exam = $attempt->exam;
        $tenantModel = app(Tenancy::class)->current();
        $resolver = app(GatewayCredentialResolver::class);

        $availableGateways = collect(PaymentGateway::cases())->filter(function (PaymentGateway $gateway) use ($tenantModel, $resolver) {
            $credential = $resolver->resolve($tenantModel, $gateway);

            return $credential && $credential->is_enabled;
        })->values();

        return view('cbt::certificates.pay', [
            'exam' => $exam,
            'attempt' => $attempt,
            'price' => $exam->certificatePrice(),
            'currency' => $exam->certificateCurrency(),
            'gateways' => $availableGateways,
        ]);
    }

    public function store(Request $request, string $tenant, ExamAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        $this->assertPurchasable($attempt);

        $validated = $request->validate([
            'gateway' => ['required', Rule::in(array_column(PaymentGateway::cases(), 'value'))],
        ]);

        $gateway = PaymentGateway::from($validated['gateway']);
        $exam = $attempt->exam;
        $tenantModel = app(Tenancy::class)->current();
        $credential = app(GatewayCredentialResolver::class)->resolve($tenantModel, $gateway);

        abort_unless($credential && $credential->is_enabled, 404);

        $isPlatformManaged = PlatformPaymentSettings::current()->isPlatformManaged();
        $price = $exam->certificatePrice();
        $platformFee = null;
        $platformFeeNote = null;

        // Snapshotted at creation time — a later change to the platform mode
        // or the tenant's split configuration must never rewrite a payment
        // already on the books.
        if ($isPlatformManaged) {
            $split = TenantRevenueSplit::where('tenant_id', $tenantModel->id)->first();
            $platformFee = $split ? $split->feeFor($price) : null;
            $platformFeeNote = $split?->note;
        }

        $payment = CertificatePayment::create([
            'exam_attempt_id' => $attempt->id,
            'certificate_id' => $attempt->certificate?->id,
            'user_id' => $attempt->user_id,
            'gateway' => $gateway->value,
            'amount' => $price,
            'currency' => $exam->certificateCurrency(),
            'status' => PaymentStatus::Pending->value,
            'reference' => (string) Str::uuid(),
            'collected_by' => $isPlatformManaged ? PaymentCollector::Platform->value : PaymentCollector::Tenant->value,
            'platform_fee_amount' => $platformFee,
            'platform_fee_note' => $platformFeeNote,
        ]);

        if ($gateway === PaymentGateway::BankTransfer) {
            return redirect()->route('cbt.attempts.certificate-payment.bank-transfer.show', [$attempt, $payment]);
        }

        $gatewayService = app(PaymentGatewayFactory::class)->make($gateway);
        abort_unless($gatewayService, 404);

        return redirect()->away($gatewayService->checkoutUrl($payment, $credential));
    }

    public function bankTransferShow(Request $request, string $tenant, ExamAttempt $attempt, CertificatePayment $payment): View
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_unless($payment->exam_attempt_id === $attempt->id && $payment->gateway === PaymentGateway::BankTransfer, 404);

        return view('cbt::certificates.bank-transfer', [
            'attempt' => $attempt,
            'payment' => $payment,
            'settings' => $attempt->exam->certificateSettings(),
        ]);
    }

    public function bankTransferReport(Request $request, string $tenant, ExamAttempt $attempt, CertificatePayment $payment): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_unless($payment->exam_attempt_id === $attempt->id && $payment->gateway === PaymentGateway::BankTransfer, 404);
        abort_unless($payment->isPending(), 404);

        $payment->update(['metadata' => array_merge($payment->metadata ?? [], ['reported_paid_at' => now()->toIso8601String()])]);

        return redirect()
            ->route('cbt.attempts.result', $attempt)
            ->with('status', "Thanks — we've recorded your payment claim. The organization will confirm it shortly.");
    }

    private function assertPurchasable(ExamAttempt $attempt): void
    {
        abort_unless($attempt->passed(), 404);
        abort_if($attempt->certificate?->tier === CertificateTier::Verified, 404);
    }
}
