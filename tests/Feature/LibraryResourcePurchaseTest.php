<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryResourcePurchaseTest extends TestCase
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
            'is_published' => true, 'requires_checkout' => true, 'total_copies' => 5,
            'pricing_policy' => 'paid', 'price' => 15, 'currency' => 'USD',
        ]);
    }

    private function enableBankTransfer(User $owner): void
    {
        $this->actingAs($owner)->put('/t/acme/library/manage/payment-gateways/bank_transfer', ['is_enabled' => '1']);
    }

    public function test_borrowing_an_unpurchased_paid_resource_is_blocked(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $resource = $this->paidResource($tenant);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/borrow')->assertForbidden();
    }

    public function test_bank_transfer_confirm_grants_purchase_then_borrowing_still_respects_copy_availability(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableBankTransfer($owner);
        $resource = $this->paidResource($tenant);
        $resource->update(['total_copies' => 1]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $otherBorrower = User::factory()->create(['tenant_id' => $tenant->id]);

        // Someone else already holds the only copy.
        $this->actingAs($owner); // no-op just to keep pattern consistent
        DB::table('library_checkouts')->insert([
            'tenant_id' => $tenant->id, 'resource_id' => $resource->id, 'user_id' => $otherBorrower->id,
            'checked_out_at' => now(), 'due_at' => now()->addDays(14), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/purchase', ['gateway' => 'bank_transfer']);
        $purchase = LibraryResourcePurchase::where('resource_id', $resource->id)->where('user_id', $user->id)->first();

        $this->actingAs($owner)->post("/t/acme/library/manage/resource-purchases/{$purchase->id}/confirm")->assertRedirect();
        $this->assertTrue($resource->fresh()->isPurchasedBy($user->fresh()));

        // Purchased, but the copy is still taken — borrowing joins the waitlist, not a checkout.
        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/borrow');
        $this->assertNull(\Elibrary\Library\Models\LibraryCheckout::where('user_id', $user->id)->first());
        $this->assertNotNull(\Elibrary\Library\Models\LibraryHold::where('user_id', $user->id)->first());
    }

    public function test_the_pending_queue_is_tenant_isolated(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner('acme');
        [$tenantB, $ownerB] = $this->tenantWithOwner('other');
        $this->enableBankTransfer($ownerA);
        $resource = $this->paidResource($tenantA);
        $user = User::factory()->create(['tenant_id' => $tenantA->id]);

        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/purchase', ['gateway' => 'bank_transfer']);
        $purchase = LibraryResourcePurchase::where('resource_id', $resource->id)->first();

        $html = $this->actingAs($ownerB)->get('/t/other/library/manage/resource-purchases')->assertOk()->getContent();
        $this->assertStringNotContainsString($user->email, $html);

        $this->actingAs($ownerB)->post("/t/other/library/manage/resource-purchases/{$purchase->id}/confirm")->assertNotFound();
    }

    public function test_gateway_credentials_are_stored_encrypted_not_as_plaintext(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)->put('/t/acme/library/manage/payment-gateways/stripe', [
            'is_enabled' => '1',
            'secret_key' => 'sk_test_super_secret_value',
        ]);

        $raw = DB::table('library_payment_gateway_credentials')->where('tenant_id', $tenant->id)->where('gateway', 'stripe')->first();
        $this->assertNotNull($raw);
        $this->assertStringNotContainsString('sk_test_super_secret_value', $raw->credentials);

        $credential = LibraryPaymentGatewayCredential::where('tenant_id', $tenant->id)->where('gateway', 'stripe')->first();
        $this->assertSame('sk_test_super_secret_value', $credential->credentials['secret_key']);
    }
}
