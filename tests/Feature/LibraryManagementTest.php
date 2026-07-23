<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    public function test_a_member_cannot_access_resource_management(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($member)->get('/t/acme/library/manage/resources')->assertForbidden();
    }

    public function test_an_owner_can_create_a_draft_resource_invisible_to_the_public_catalog(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Silent Spring',
            'slug' => 'silent-spring',
            'category' => 'Science',
            'author' => 'Rachel Carson',
        ])->assertRedirect();

        $resource = LibraryResource::where('slug', 'silent-spring')->first();
        $this->assertNotNull($resource);
        $this->assertFalse($resource->is_published);

        $this->actingAs($owner)->get('/t/acme/library')->assertDontSee('Silent Spring');
    }

    public function test_publishing_a_resource_makes_it_visible_on_the_catalog(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = LibraryResource::create([
            'tenant_id' => $tenant->id, 'title' => 'Silent Spring', 'slug' => 'silent-spring',
            'category' => 'Science', 'is_published' => false,
        ]);

        $this->actingAs($owner)->put("/t/acme/library/manage/resources/{$resource->slug}", [
            'title' => 'Silent Spring',
            'slug' => 'silent-spring',
            'category' => 'Science',
            'is_published' => '1',
        ])->assertRedirect();

        $this->assertTrue($resource->fresh()->is_published);
        $this->actingAs($owner)->get('/t/acme/library')->assertSee('Silent Spring');
    }

    public function test_an_owner_can_delete_a_resource(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = LibraryResource::create([
            'tenant_id' => $tenant->id, 'title' => 'Silent Spring', 'slug' => 'silent-spring',
            'category' => 'Science', 'is_published' => true,
        ]);

        $this->actingAs($owner)
            ->delete("/t/acme/library/manage/resources/{$resource->slug}")
            ->assertRedirect();

        $this->assertNull(LibraryResource::find($resource->id));
    }

    public function test_an_unpublished_resource_404s_for_learners(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = LibraryResource::create([
            'tenant_id' => $tenant->id, 'title' => 'Draft Book', 'slug' => 'draft-book',
            'category' => 'Science', 'is_published' => false,
        ]);
        $learner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($learner)
            ->get("/t/acme/library/resources/{$resource->slug}")
            ->assertNotFound();
    }
}
