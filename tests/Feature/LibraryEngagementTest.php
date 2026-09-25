<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryFavorite;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryEngagementTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithLibrary(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);

        return $tenant;
    }

    private function resource(Tenant $tenant): LibraryResource
    {
        return LibraryResource::create([
            'tenant_id' => $tenant->id, 'title' => 'Physics 101', 'slug' => 'physics-101',
            'category' => 'Science', 'is_published' => true,
        ]);
    }

    public function test_rating_creates_then_updates_the_same_row_and_affects_the_average(): void
    {
        $tenant = $this->tenantWithLibrary();
        $resource = $this->resource($tenant);
        $userA = User::factory()->create(['tenant_id' => $tenant->id]);
        $userB = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($userA)->post('/t/acme/library/resources/physics-101/ratings', ['stars' => 4]);
        $this->actingAs($userB)->post('/t/acme/library/resources/physics-101/ratings', ['stars' => 2]);

        $this->assertSame(2, $resource->fresh()->ratingsCount());
        $this->assertSame(3.0, $resource->fresh()->averageRating());

        // Updating the same user's rating replaces it, not adds a new row.
        $this->actingAs($userA)->post('/t/acme/library/resources/physics-101/ratings', ['stars' => 5]);

        $this->assertSame(2, $resource->fresh()->ratingsCount());
        $this->assertSame(3.5, $resource->fresh()->averageRating());
        $this->assertSame(1, LibraryResourceRating::where('resource_id', $resource->id)->where('user_id', $userA->id)->count());
    }

    public function test_favorite_toggle_adds_and_removes(): void
    {
        $tenant = $this->tenantWithLibrary();
        $resource = $this->resource($tenant);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post('/t/acme/library/resources/physics-101/favorite')->assertRedirect();
        $this->assertTrue(LibraryFavorite::where('resource_id', $resource->id)->where('user_id', $user->id)->exists());

        $html = $this->actingAs($user)->get('/t/acme/library/my-favorites')->assertOk()->getContent();
        $this->assertStringContainsString('Physics 101', $html);

        $this->actingAs($user)->delete('/t/acme/library/resources/physics-101/favorite')->assertRedirect();
        $this->assertFalse(LibraryFavorite::where('resource_id', $resource->id)->where('user_id', $user->id)->exists());
    }

    public function test_tenant_isolation_on_ratings_and_favorites(): void
    {
        $tenantA = $this->tenantWithLibrary();
        $tenantB = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        $tenantB->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);

        $resourceA = $this->resource($tenantA);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($userB)->post('/t/other/library/resources/physics-101/favorite')->assertNotFound();
    }
}
