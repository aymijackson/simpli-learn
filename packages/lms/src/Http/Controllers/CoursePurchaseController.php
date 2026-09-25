<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Enums\CoursePaymentGateway;
use Elibrary\Lms\Enums\CoursePurchaseStatus;
use Elibrary\Lms\Enums\CoursePurchaseType;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CoursePaymentGatewayCredential;
use Elibrary\Lms\Models\CoursePurchase;
use Elibrary\Lms\Payments\PaymentGatewayFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CoursePurchaseController extends Controller
{
    public function create(Request $request, string $tenant, Course $course): View
    {
        abort_if($course->isEnrolled($request->user()), 404);

        return view('lms::courses.purchase', [
            'course' => $course,
            'purchaseType' => CoursePurchaseType::Enrollment,
            'price' => $course->price,
            'currency' => $course->currency,
            'gateways' => $this->availableGateways(),
        ]);
    }

    public function store(Request $request, string $tenant, Course $course): RedirectResponse
    {
        abort_if($course->isEnrolled($request->user()), 404);

        return $this->purchase($request, $course, CoursePurchaseType::Enrollment, $course->price, $course->currency);
    }

    public function createCertificate(Request $request, string $tenant, Course $course): View
    {
        $this->assertCertificatePurchasable($course, $request->user());

        return view('lms::courses.purchase', [
            'course' => $course,
            'purchaseType' => CoursePurchaseType::Certificate,
            'price' => $course->certificate_price,
            'currency' => $course->certificate_currency,
            'gateways' => $this->availableGateways(),
        ]);
    }

    public function storeCertificate(Request $request, string $tenant, Course $course): RedirectResponse
    {
        $this->assertCertificatePurchasable($course, $request->user());

        return $this->purchase($request, $course, CoursePurchaseType::Certificate, $course->certificate_price, $course->certificate_currency);
    }

    private function purchase(Request $request, Course $course, CoursePurchaseType $purchaseType, ?float $price, ?string $currency): RedirectResponse
    {
        $validated = $request->validate([
            'gateway' => ['required', Rule::in(array_column(CoursePaymentGateway::cases(), 'value'))],
        ]);

        $gateway = CoursePaymentGateway::from($validated['gateway']);
        $tenantId = app(Tenancy::class)->id();
        $credential = CoursePaymentGatewayCredential::where('tenant_id', $tenantId)
            ->where('gateway', $gateway->value)
            ->first();

        abort_unless($credential && $credential->is_enabled, 404);

        $purchase = CoursePurchase::create([
            'course_id' => $course->id,
            'user_id' => $request->user()->id,
            'purchase_type' => $purchaseType->value,
            'gateway' => $gateway->value,
            'amount' => $price,
            'currency' => $currency,
            'status' => CoursePurchaseStatus::Pending->value,
            'reference' => (string) Str::uuid(),
        ]);

        if ($gateway === CoursePaymentGateway::BankTransfer) {
            return redirect()->route('lms.courses.purchase.bank-transfer.show', [$course, $purchase]);
        }

        $gatewayService = app(PaymentGatewayFactory::class)->make($gateway);
        abort_unless($gatewayService, 404);

        return redirect()->away($gatewayService->checkoutUrl($purchase, $credential));
    }

    private function assertCertificatePurchasable(Course $course, $user): void
    {
        abort_unless($course->certificate_policy->value === 'paid', 404);
        abort_unless($course->isPassedBy($user) || $course->isEnrolled($user), 404);
        abort_if($course->certificateFor($user), 404);
    }

    private function availableGateways(): \Illuminate\Support\Collection
    {
        $tenantId = app(Tenancy::class)->id();

        return collect(CoursePaymentGateway::cases())->filter(
            fn (CoursePaymentGateway $gateway) => CoursePaymentGatewayCredential::where('tenant_id', $tenantId)
                ->where('gateway', $gateway->value)
                ->where('is_enabled', true)
                ->exists()
        )->values();
    }

    public function bankTransferShow(Request $request, string $tenant, Course $course, CoursePurchase $purchase): View
    {
        abort_unless($purchase->course_id === $course->id && $purchase->user_id === $request->user()->id, 404);
        abort_unless($purchase->gateway === CoursePaymentGateway::BankTransfer, 404);

        $tenantId = app(Tenancy::class)->id();
        $bankCredential = CoursePaymentGatewayCredential::where('tenant_id', $tenantId)
            ->where('gateway', CoursePaymentGateway::BankTransfer->value)
            ->first();

        return view('lms::courses.bank-transfer', [
            'course' => $course,
            'purchase' => $purchase,
            'bankInstructions' => $bankCredential?->credentials['public_key'] ?? null,
        ]);
    }

    public function bankTransferReport(Request $request, string $tenant, Course $course, CoursePurchase $purchase): RedirectResponse
    {
        abort_unless($purchase->course_id === $course->id && $purchase->user_id === $request->user()->id, 404);
        abort_unless($purchase->gateway === CoursePaymentGateway::BankTransfer, 404);
        abort_unless($purchase->isPending(), 404);

        $purchase->update(['metadata' => array_merge($purchase->metadata ?? [], ['reported_paid_at' => now()->toIso8601String()])]);

        return redirect()
            ->route('lms.courses.show', $course)
            ->with('status', "Thanks — we've recorded your payment claim. The organization will confirm it shortly.");
    }
}
