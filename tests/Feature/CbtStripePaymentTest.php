<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Enums\PaymentCollector;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Elibrary\Cbt\Payments\PaymentGatewayFactory;
use Elibrary\Cbt\Payments\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class CbtStripePaymentTest extends TestCase
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

    /** Builds a genuine Stripe-format signature header offline (HMAC-SHA256), matching Stripe\WebhookSignature's own algorithm — no network call needed. */
    private function stripeSignatureHeader(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$signature}";
    }

    public function test_the_factory_resolves_stripe_to_a_contract_implementation(): void
    {
        $gateway = app(PaymentGatewayFactory::class)->make(PaymentGateway::Stripe);

        $this->assertInstanceOf(StripeGateway::class, $gateway);
        $this->assertInstanceOf(\Elibrary\Cbt\Payments\PaymentGatewayContract::class, $gateway);
    }

    public function test_a_valid_signature_is_accepted_and_an_invalid_one_is_rejected(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $credential = PaymentGatewayCredential::create([
            'tenant_id' => $tenant->id,
            'gateway' => 'stripe',
            'is_enabled' => true,
            'credentials' => ['secret_key' => 'sk_test_x', 'webhook_secret' => 'whsec_test_secret'],
        ]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['client_reference_id' => 'abc123']]]);
        $gatewayService = new StripeGateway;

        $validHeader = $this->stripeSignatureHeader($payload, 'whsec_test_secret');
        $validRequest = Request::create('/webhooks/certificates/stripe', 'POST', [], [], [], [], $payload);
        $validRequest->headers->set('Stripe-Signature', $validHeader);
        $this->assertTrue($gatewayService->verifyWebhookSignature($validRequest, $credential));

        $invalidHeader = $this->stripeSignatureHeader($payload, 'wrong_secret');
        $invalidRequest = Request::create('/webhooks/certificates/stripe', 'POST', [], [], [], [], $payload);
        $invalidRequest->headers->set('Stripe-Signature', $invalidHeader);
        $this->assertFalse($gatewayService->verifyWebhookSignature($invalidRequest, $credential));
    }

    public function test_resolve_payment_from_webhook_finds_the_matching_payment_across_any_tenant(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $exam->attempts()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'started_at' => now(), 'submitted_at' => now(), 'score' => 100]);

        $payment = CertificatePayment::create([
            'tenant_id' => $tenant->id,
            'exam_attempt_id' => $attempt->id,
            'user_id' => $user->id,
            'gateway' => 'stripe',
            'amount' => 10,
            'currency' => 'USD',
            'status' => 'pending',
            'reference' => (string) Str::uuid(),
            'collected_by' => PaymentCollector::Tenant->value,
        ]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['client_reference_id' => $payment->reference]]]);
        $request = Request::create('/webhooks/certificates/stripe', 'POST', [], [], [], [], $payload);

        $resolved = (new StripeGateway)->resolvePaymentFromWebhook($request);

        $this->assertNotNull($resolved);
        $this->assertSame($payment->id, $resolved->id);
    }

    public function test_resolve_payment_from_webhook_returns_null_for_an_unknown_reference(): void
    {
        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['client_reference_id' => 'no-such-reference']]]);
        $request = Request::create('/webhooks/certificates/stripe', 'POST', [], [], [], [], $payload);

        $this->assertNull((new StripeGateway)->resolvePaymentFromWebhook($request));
    }

    public function test_the_webhook_endpoint_rejects_an_invalid_signature_and_does_not_mark_the_payment_paid(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $exam->attempts()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'started_at' => now(), 'submitted_at' => now(), 'score' => 100]);

        PaymentGatewayCredential::create([
            'tenant_id' => $tenant->id,
            'gateway' => 'stripe',
            'is_enabled' => true,
            'credentials' => ['secret_key' => 'sk_test_x', 'webhook_secret' => 'whsec_test_secret'],
        ]);

        $payment = CertificatePayment::create([
            'tenant_id' => $tenant->id,
            'exam_attempt_id' => $attempt->id,
            'user_id' => $user->id,
            'gateway' => 'stripe',
            'amount' => 10,
            'currency' => 'USD',
            'status' => 'pending',
            'reference' => (string) Str::uuid(),
            'collected_by' => PaymentCollector::Tenant->value,
        ]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['client_reference_id' => $payment->reference]]]);

        $response = $this->call('POST', '/webhooks/certificates/stripe', [], [], [], [
            'HTTP_Stripe-Signature' => 'v1=bogus,t='.time(),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertForbidden();
        $this->assertSame('pending', $payment->fresh()->status->value);
    }

    public function test_an_unimplemented_gateway_name_on_the_webhook_route_404s(): void
    {
        $this->post('/webhooks/certificates/not-a-real-gateway')->assertNotFound();
    }
}
