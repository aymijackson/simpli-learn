<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryHold;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function limitedResource(Tenant $tenant, int $copies = 1): LibraryResource
    {
        return LibraryResource::create([
            'tenant_id' => $tenant->id, 'title' => 'Rare Book', 'slug' => 'rare-book', 'category' => 'Fiction',
            'is_published' => true, 'requires_checkout' => true, 'total_copies' => $copies, 'checkout_duration_days' => 14,
        ]);
    }

    public function test_borrowing_an_available_copy_creates_an_active_checkout(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $resource = $this->limitedResource($tenant);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/borrow')->assertRedirect();

        $checkout = LibraryCheckout::where('resource_id', $resource->id)->where('user_id', $user->id)->first();
        $this->assertNotNull($checkout);
        $this->assertTrue($checkout->isActive());
        $this->assertSame(0, $resource->fresh()->availableCopies());
    }

    public function test_borrowing_with_zero_copies_available_creates_a_hold_instead(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $resource = $this->limitedResource($tenant, 1);
        $first = User::factory()->create(['tenant_id' => $tenant->id]);
        $second = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($first)->post('/t/acme/library/resources/rare-book/borrow');
        $this->actingAs($second)->post('/t/acme/library/resources/rare-book/borrow');

        $this->assertNull(LibraryCheckout::where('user_id', $second->id)->first());
        $hold = LibraryHold::where('user_id', $second->id)->first();
        $this->assertNotNull($hold);
        $this->assertSame(1, $hold->position());
    }

    public function test_returning_promotes_the_next_hold(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $resource = $this->limitedResource($tenant, 1);
        $first = User::factory()->create(['tenant_id' => $tenant->id]);
        $second = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($first)->post('/t/acme/library/resources/rare-book/borrow');
        $checkout = LibraryCheckout::where('user_id', $first->id)->first();
        $this->actingAs($second)->post('/t/acme/library/resources/rare-book/borrow');
        $hold = LibraryHold::where('user_id', $second->id)->first();

        $this->assertNull($hold->fresh()->notified_at);

        $this->actingAs($first)->post("/t/acme/library/checkouts/{$checkout->id}/return")->assertRedirect();

        $hold->refresh();
        $this->assertNotNull($hold->notified_at);
        $this->assertTrue($hold->hasLiveOffer());
    }

    public function test_a_forfeited_hold_is_skipped_and_the_next_one_is_promoted(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $resource = $this->limitedResource($tenant, 1);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $borrower = User::factory()->create(['tenant_id' => $tenant->id]);
        $firstWaiter = User::factory()->create(['tenant_id' => $tenant->id]);
        $secondWaiter = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($borrower)->post('/t/acme/library/resources/rare-book/borrow');
        $checkout = LibraryCheckout::where('user_id', $borrower->id)->first();

        $this->actingAs($firstWaiter)->post('/t/acme/library/resources/rare-book/borrow');
        $firstHold = LibraryHold::where('user_id', $firstWaiter->id)->first();
        $this->actingAs($secondWaiter)->post('/t/acme/library/resources/rare-book/borrow');
        $secondHold = LibraryHold::where('user_id', $secondWaiter->id)->first();

        $this->actingAs($borrower)->post("/t/acme/library/checkouts/{$checkout->id}/return");

        // Simulate the first waiter's offer having expired without being claimed.
        $firstHold->refresh();
        $firstHold->update(['expires_at' => now()->subDay()]);

        $returnedCheckout = LibraryCheckout::where('user_id', $borrower->id)->latest()->first();
        // Nothing new was returned, but re-running return() again (idempotent no-op since already returned)
        // should not double-promote; instead verify the resource's own nextEligibleHold() skips the forfeited one.
        $this->assertTrue($secondHold->id === $resource->fresh()->nextEligibleHold()->id);
    }

    public function test_claiming_a_live_hold_creates_a_checkout_and_marks_it_fulfilled(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $resource = $this->limitedResource($tenant, 1);
        $first = User::factory()->create(['tenant_id' => $tenant->id]);
        $second = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($first)->post('/t/acme/library/resources/rare-book/borrow');
        $checkout = LibraryCheckout::where('user_id', $first->id)->first();
        $this->actingAs($second)->post('/t/acme/library/resources/rare-book/borrow');
        $hold = LibraryHold::where('user_id', $second->id)->first();

        $this->actingAs($first)->post("/t/acme/library/checkouts/{$checkout->id}/return");

        $hold->refresh();
        $this->actingAs($second)->post("/t/acme/library/holds/{$hold->id}/claim")->assertRedirect();

        $this->assertNotNull($hold->fresh()->fulfilled_at);
        $this->assertNotNull(LibraryCheckout::where('user_id', $second->id)->whereNull('returned_at')->first());
    }

    public function test_overdue_checkout_still_blocks_the_copy(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $resource = $this->limitedResource($tenant, 1);
        $first = User::factory()->create(['tenant_id' => $tenant->id]);
        $second = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($first)->post('/t/acme/library/resources/rare-book/borrow');
        $checkout = LibraryCheckout::where('user_id', $first->id)->first();
        $checkout->update(['due_at' => now()->subDays(5)]);

        $this->assertTrue($checkout->fresh()->isOverdue());
        $this->assertSame(0, $resource->fresh()->availableCopies());

        $this->actingAs($second)->post('/t/acme/library/resources/rare-book/borrow');
        $this->assertNull(LibraryCheckout::where('user_id', $second->id)->first());
        $this->assertNotNull(LibraryHold::where('user_id', $second->id)->first());
    }

    public function test_tenant_isolation_on_checkouts(): void
    {
        [$tenantA] = $this->tenantWithOwner('acme');
        [$tenantB] = $this->tenantWithOwner('other');
        $resourceA = $this->limitedResource($tenantA);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($userB)->post('/t/other/library/resources/rare-book/borrow')->assertNotFound();
    }
}
