<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Elibrary\Library\Payments\PaymentGatewayContract;
use Elibrary\Library\Payments\PaymentGatewayFactory;
use Elibrary\Library\Payments\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class LibraryStripePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function stripeSignatureHeader(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$signature}";
    }

    public function test_the_factory_resolves_stripe_to_a_contract_implementation(): void
    {
        $gateway = app(PaymentGatewayFactory::class)->make(LibraryPaymentGateway::Stripe);

        $this->assertInstanceOf(StripeGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayContract::class, $gateway);
    }

    public function test_a_valid_signature_is_accepted_and_an_invalid_one_is_rejected(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $credential = LibraryPaymentGatewayCredential::create([
            'tenant_id' => $tenant->id, 'gateway' => 'stripe', 'is_enabled' => true,
            'credentials' => ['secret_key' => 'sk_test_x', 'webhook_secret' => 'whsec_test_secret'],
        ]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['client_reference_id' => 'abc123']]]);
        $gatewayService = new StripeGateway;

        $validHeader = $this->stripeSignatureHeader($payload, 'whsec_test_secret');
        $validRequest = Request::create('/webhooks/library/stripe', 'POST', [], [], [], [], $payload);
        $validRequest->headers->set('Stripe-Signature', $validHeader);
        $this->assertTrue($gatewayService->verifyWebhookSignature($validRequest, $credential));

        $invalidHeader = $this->stripeSignatureHeader($payload, 'wrong_secret');
        $invalidRequest = Request::create('/webhooks/library/stripe', 'POST', [], [], [], [], $payload);
        $invalidRequest->headers->set('Stripe-Signature', $invalidHeader);
        $this->assertFalse($gatewayService->verifyWebhookSignature($invalidRequest, $credential));
    }

    public function test_the_webhook_endpoint_rejects_an_invalid_signature_and_does_not_mark_the_purchase_paid(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $resource = LibraryResource::create(['tenant_id' => $tenant->id, 'title' => 'Rare Book', 'slug' => 'rare-book', 'category' => 'Fiction', 'is_published' => true, 'pricing_policy' => 'paid', 'price' => 10, 'currency' => 'USD']);

        LibraryPaymentGatewayCredential::create([
            'tenant_id' => $tenant->id, 'gateway' => 'stripe', 'is_enabled' => true,
            'credentials' => ['secret_key' => 'sk_test_x', 'webhook_secret' => 'whsec_test_secret'],
        ]);

        $purchase = LibraryResourcePurchase::create([
            'tenant_id' => $tenant->id, 'resource_id' => $resource->id, 'user_id' => $user->id,
            'gateway' => 'stripe', 'amount' => 10, 'currency' => 'USD',
            'status' => 'pending', 'reference' => (string) Str::uuid(),
        ]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['client_reference_id' => $purchase->reference]]]);

        $response = $this->call('POST', '/webhooks/library/stripe', [], [], [], [
            'HTTP_Stripe-Signature' => 'v1=bogus,t='.time(),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertForbidden();
        $this->assertSame('pending', $purchase->fresh()->status->value);
    }

    public function test_an_unimplemented_gateway_name_on_the_webhook_route_404s(): void
    {
        $this->post('/webhooks/library/not-a-real-gateway')->assertNotFound();
    }
}
