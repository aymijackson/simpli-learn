<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Elibrary\Library\Payments\PaymentGatewayFactory;
use Elibrary\Library\Payments\PaystackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LibraryPaystackPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function paidResource(Tenant $tenant): LibraryResource
    {
        return LibraryResource::create([
            'tenant_id' => $tenant->id, 'title' => 'Rare Book', 'slug' => 'rare-book', 'category' => 'Fiction',
            'is_published' => true, 'pricing_policy' => 'paid', 'price' => 10, 'currency' => 'USD',
        ]);
    }

    private function enablePaystack(User $owner, string $secretKey = 'sk_test_paystack'): void
    {
        $this->actingAs($owner)->put('/t/acme/library/manage/payment-gateways/paystack', [
            'is_enabled' => '1',
            'secret_key' => $secretKey,
        ]);
    }

    public function test_the_factory_resolves_paystack_to_a_contract_implementation(): void
    {
        $gateway = app(PaymentGatewayFactory::class)->make(LibraryPaymentGateway::Paystack);

        $this->assertInstanceOf(PaystackGateway::class, $gateway);
    }

    public function test_choosing_paystack_redirects_the_learner_to_the_authorization_url(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/fake-session'],
            ]),
        ]);

        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enablePaystack($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $resource = $this->paidResource($tenant);

        $response = $this->actingAs($user)->post('/t/acme/library/resources/rare-book/purchase', ['gateway' => 'paystack']);

        $response->assertRedirect('https://checkout.paystack.com/fake-session');

        $purchase = LibraryResourcePurchase::where('resource_id', $resource->id)->first();
        Http::assertSent(fn ($request) => $request->url() === 'https://api.paystack.co/transaction/initialize'
            && $request['reference'] === $purchase->reference
            && $request['amount'] === 1000);
    }

    public function test_a_valid_signature_webhook_marks_the_purchase_paid(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enablePaystack($owner, 'sk_test_secret');
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $resource = $this->paidResource($tenant);

        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x'],
        ])]);
        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/purchase', ['gateway' => 'paystack']);
        $purchase = LibraryResourcePurchase::where('resource_id', $resource->id)->first();

        Http::fake(["api.paystack.co/transaction/verify/{$purchase->reference}" => Http::response([
            'status' => true, 'data' => ['status' => 'success', 'reference' => $purchase->reference],
        ])]);

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $purchase->reference, 'status' => 'success']]);
        $signature = hash_hmac('sha512', $payload, 'sk_test_secret');

        $response = $this->call('POST', '/webhooks/library/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertNoContent();

        $purchase->refresh();
        $this->assertSame('paid', $purchase->status->value);
        $this->assertTrue($resource->fresh()->isPurchasedBy($user->fresh()));
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enablePaystack($owner, 'sk_test_secret');
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $resource = $this->paidResource($tenant);

        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x'],
        ])]);
        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/purchase', ['gateway' => 'paystack']);
        $purchase = LibraryResourcePurchase::where('resource_id', $resource->id)->first();

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $purchase->reference, 'status' => 'success']]);

        $response = $this->call('POST', '/webhooks/library/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => 'not-the-right-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertForbidden();
        $this->assertSame('pending', $purchase->fresh()->status->value);
        $this->assertFalse($resource->fresh()->isPurchasedBy($user->fresh()));
    }
}
