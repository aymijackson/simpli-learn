<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    public function test_a_member_cannot_access_team_management(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($member)->get('/t/acme/team')->assertForbidden();
    }

    public function test_an_owner_can_add_a_team_member(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $response = $this->actingAs($owner)->post('/t/acme/team', [
            'name' => 'Jordan Member',
            'email' => 'jordan@acme.test',
            'role' => 'member',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'jordan@acme.test',
            'role' => 'member',
        ]);

        $this->actingAs($owner)->get('/t/acme/team')->assertSee('Jordan Member');
    }

    public function test_an_owner_can_promote_a_member_to_owner(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($owner)
            ->put("/t/acme/team/{$member->id}", ['role' => 'owner'])
            ->assertRedirect();

        $this->assertTrue($member->fresh()->isOwner());
    }

    public function test_an_owner_can_remove_a_member(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($owner)
            ->delete("/t/acme/team/{$member->id}")
            ->assertRedirect();

        $this->assertNull(User::find($member->id));
    }

    public function test_an_owner_cannot_remove_themselves(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)
            ->delete("/t/acme/team/{$owner->id}")
            ->assertRedirect();

        $this->assertNotNull(User::find($owner->id));
    }

    public function test_the_last_owner_cannot_be_demoted(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)
            ->put("/t/acme/team/{$owner->id}", ['role' => 'member'])
            ->assertSessionHasErrors('role');

        $this->assertTrue($owner->fresh()->isOwner());
    }

    public function test_an_owner_can_be_demoted_when_another_owner_remains(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $secondOwner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)
            ->put("/t/acme/team/{$secondOwner->id}", ['role' => 'member'])
            ->assertRedirect();

        $this->assertFalse($secondOwner->fresh()->isOwner());
        $this->assertTrue($owner->fresh()->isOwner());
    }
}
