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
use Elibrary\Library\Payments\FlutterwaveGateway;
use Elibrary\Library\Payments\PaymentGatewayFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LibraryFlutterwavePaymentTest extends TestCase
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
            'is_published' => true, 'pricing_policy' => 'paid', 'price' => 15, 'currency' => 'NGN',
        ]);
    }

    private function enableFlutterwave(User $owner, string $secretKey = 'FLWSECK_TEST-x'): void
    {
        $this->actingAs($owner)->put('/t/acme/library/manage/payment-gateways/flutterwave', [
            'is_enabled' => '1',
            'secret_key' => $secretKey,
            'webhook_secret' => 'my-configured-hash',
        ]);
    }

    public function test_the_factory_resolves_flutterwave_to_a_contract_implementation(): void
    {
        $gateway = app(PaymentGatewayFactory::class)->make(LibraryPaymentGateway::Flutterwave);

        $this->assertInstanceOf(FlutterwaveGateway::class, $gateway);
    }

    public function test_choosing_flutterwave_redirects_the_learner_to_the_checkout_link(): void
    {
        Http::fake([
            'api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => ['link' => 'https://checkout.flutterwave.com/fake-session'],
            ]),
        ]);

        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableFlutterwave($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $resource = $this->paidResource($tenant);

        $response = $this->actingAs($user)->post('/t/acme/library/resources/rare-book/purchase', ['gateway' => 'flutterwave']);

        $response->assertRedirect('https://checkout.flutterwave.com/fake-session');

        $purchase = LibraryResourcePurchase::where('resource_id', $resource->id)->first();
        Http::assertSent(fn ($request) => $request->url() === 'https://api.flutterwave.com/v3/payments'
            && $request['tx_ref'] === $purchase->reference
            && $request['currency'] === 'NGN');
    }

    public function test_a_valid_webhook_hash_marks_the_purchase_paid(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableFlutterwave($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $resource = $this->paidResource($tenant);

        Http::fake(['api.flutterwave.com/v3/payments' => Http::response([
            'status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/x'],
        ])]);
        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/purchase', ['gateway' => 'flutterwave']);
        $purchase = LibraryResourcePurchase::where('resource_id', $resource->id)->first();

        Http::fake(['api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response([
            'status' => 'success', 'data' => ['status' => 'successful', 'id' => 998877, 'tx_ref' => $purchase->reference],
        ])]);

        $payload = json_encode(['event' => 'charge.completed', 'data' => ['tx_ref' => $purchase->reference, 'status' => 'successful']]);

        $response = $this->call('POST', '/webhooks/library/flutterwave', [], [], [], [
            'HTTP_verif-hash' => 'my-configured-hash',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertNoContent();

        $purchase->refresh();
        $this->assertSame('paid', $purchase->status->value);
        $this->assertSame('998877', $purchase->gateway_reference);
        $this->assertTrue($resource->fresh()->isPurchasedBy($user->fresh()));
    }

    public function test_a_wrong_hash_is_rejected(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableFlutterwave($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $resource = $this->paidResource($tenant);

        Http::fake(['api.flutterwave.com/v3/payments' => Http::response([
            'status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/x'],
        ])]);
        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/purchase', ['gateway' => 'flutterwave']);
        $purchase = LibraryResourcePurchase::where('resource_id', $resource->id)->first();

        $payload = json_encode(['event' => 'charge.completed', 'data' => ['tx_ref' => $purchase->reference, 'status' => 'successful']]);

        $response = $this->call('POST', '/webhooks/library/flutterwave', [], [], [], [
            'HTTP_verif-hash' => 'the-wrong-hash',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertForbidden();
        $this->assertSame('pending', $purchase->fresh()->status->value);
        $this->assertFalse($resource->fresh()->isPurchasedBy($user->fresh()));
    }
}
