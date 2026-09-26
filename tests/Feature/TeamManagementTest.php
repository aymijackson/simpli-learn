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

    public function test_removing_a_member_deactivates_them_and_keeps_their_records(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($owner)
            ->delete("/t/acme/team/{$member->id}")
            ->assertRedirect();

        $this->assertNotNull(User::find($member->id), 'The account must be kept, not deleted.');
        $this->assertTrue(User::find($member->id)->isDeactivated());
        $this->assertDatabaseHas('activity_logs', ['action' => 'team.member_deactivated']);
    }

    public function test_a_deactivated_member_cannot_sign_in(): void
    {
        [$tenant] = $this->tenantWithOwner();
        User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'gone@acme.test', 'password' => 'password', 'deactivated_at' => now()]);

        $this->post('/t/acme/login', ['email' => 'gone@acme.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'This account has been deactivated. Contact your workspace administrator.']);
        $this->assertGuest();
    }

    public function test_a_member_deactivated_mid_session_is_signed_out(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($member)->get('/t/acme')->assertOk();

        $member->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($member->fresh())->get('/t/acme')->assertRedirect('/t/acme/login');
        $this->assertGuest();
    }

    public function test_an_owner_can_reactivate_a_member(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'deactivated_at' => now(), 'name' => 'Jordan Member']);

        $this->actingAs($owner)->get('/t/acme/team')->assertSee('Deactivated')->assertSee('Reactivate');
        $this->actingAs($owner)->post("/t/acme/team/{$member->id}/reactivate")->assertRedirect('/t/acme/team');

        $this->assertFalse($member->fresh()->isDeactivated());
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
