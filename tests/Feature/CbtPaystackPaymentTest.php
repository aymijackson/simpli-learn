<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Payments\PaymentGatewayFactory;
use Elibrary\Cbt\Payments\PaystackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CbtPaystackPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function examWithOneQuestion(Tenant $tenant, array $attributes = []): Exam
    {
        $exam = Exam::create(array_merge([
            'tenant_id' => $tenant->id,
            'title' => 'Quiz',
            'slug' => 'quiz',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
        ], $attributes));

        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        return $exam;
    }

    private function startAttempt(User $user, Exam $exam): ExamAttempt
    {
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        return $exam->fresh()->attempts()->where('user_id', $user->id)->first();
    }

    private function submitCorrectly(User $user, Exam $exam, ExamAttempt $attempt): void
    {
        $question = $exam->questions->first();
        $correct = $question->options->firstWhere('is_correct', true);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => $correct->id],
        ]);
    }

    private function enablePaystack(User $owner, string $secretKey = 'sk_test_paystack'): void
    {
        $this->actingAs($owner)->put('/t/acme/cbt/manage/payment-gateways/paystack', [
            'is_enabled' => '1',
            'secret_key' => $secretKey,
        ]);
    }

    public function test_the_factory_resolves_paystack_to_a_contract_implementation(): void
    {
        $gateway = app(PaymentGatewayFactory::class)->make(PaymentGateway::Paystack);

        $this->assertInstanceOf(PaystackGateway::class, $gateway);
    }

    public function test_choosing_paystack_redirects_the_learner_to_the_authorization_url(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/fake-session', 'access_code' => 'abc', 'reference' => 'will-be-overridden'],
            ]),
        ]);

        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enablePaystack($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        $response = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", [
            'gateway' => 'paystack',
        ]);

        $response->assertRedirect('https://checkout.paystack.com/fake-session');

        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();
        $this->assertNotNull($payment);

        Http::assertSent(function ($request) use ($payment) {
            return $request->url() === 'https://api.paystack.co/transaction/initialize'
                && $request['reference'] === $payment->reference
                && $request['amount'] === 1000; // $10.00 -> 1000 kobo
        });
    }

    public function test_a_valid_signature_webhook_marks_the_payment_paid_and_issues_the_certificate(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enablePaystack($owner, 'sk_test_secret');
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x'],
        ])]);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'paystack']);
        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();

        Http::fake(["api.paystack.co/transaction/verify/{$payment->reference}" => Http::response([
            'status' => true, 'data' => ['status' => 'success', 'reference' => $payment->reference],
        ])]);

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference, 'status' => 'success']]);
        $signature = hash_hmac('sha512', $payload, 'sk_test_secret');

        $response = $this->call('POST', '/webhooks/certificates/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertNoContent();

        $payment->refresh();
        $this->assertSame('paid', $payment->status->value);
        $this->assertNotNull($attempt->fresh()->certificate);
        $this->assertSame('verified', $attempt->fresh()->certificate->tier->value);
    }

    public function test_an_invalid_signature_is_rejected_and_does_not_mark_the_payment_paid(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enablePaystack($owner, 'sk_test_secret');
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x'],
        ])]);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'paystack']);
        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference, 'status' => 'success']]);

        $response = $this->call('POST', '/webhooks/certificates/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => 'not-the-right-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertForbidden();
        $this->assertSame('pending', $payment->fresh()->status->value);
        $this->assertNull($attempt->fresh()->certificate);
    }

    public function test_cross_tenant_reference_resolves_the_correct_tenants_credential(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner('acme');
        [$tenantB, $ownerB] = $this->tenantWithOwner('other');
        $this->enablePaystack($ownerA, 'secret_for_a');
        $this->actingAs($ownerB)->put('/t/other/cbt/manage/payment-gateways/paystack', ['is_enabled' => '1', 'secret_key' => 'secret_for_b']);

        $user = User::factory()->create(['tenant_id' => $tenantA->id]);
        $exam = $this->examWithOneQuestion($tenantA, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x'],
        ])]);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'paystack']);
        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();
        $this->assertSame($tenantA->id, $payment->tenant_id);

        Http::fake(["api.paystack.co/transaction/verify/{$payment->reference}" => Http::response([
            'status' => true, 'data' => ['status' => 'success', 'reference' => $payment->reference],
        ])]);

        // Signed with tenant A's secret (the correct one) — must be accepted.
        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference, 'status' => 'success']]);
        $signature = hash_hmac('sha512', $payload, 'secret_for_a');

        $this->call('POST', '/webhooks/certificates/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertNoContent();

        $this->assertSame('paid', $payment->fresh()->status->value);
    }
}
