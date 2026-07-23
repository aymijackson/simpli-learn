<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function centralAdmin(): User
    {
        return User::factory()->create(['tenant_id' => null]);
    }

    public function test_guests_cannot_reach_the_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_the_dashboard_shows_counts_and_the_pending_queue(): void
    {
        $admin = $this->centralAdmin();
        Tenant::create(['name' => 'Riverside School', 'slug' => 'riverside', 'status' => TenantStatus::Pending]);
        Tenant::create(['name' => 'Acme University', 'slug' => 'acme', 'status' => TenantStatus::Active]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Riverside School');
        $response->assertDontSee('Acme University');
    }

    public function test_the_tenants_index_lists_every_tenant_regardless_of_status(): void
    {
        $admin = $this->centralAdmin();
        Tenant::create(['name' => 'Riverside School', 'slug' => 'riverside', 'status' => TenantStatus::Pending]);
        Tenant::create(['name' => 'Acme University', 'slug' => 'acme', 'status' => TenantStatus::Active]);

        $response = $this->actingAs($admin)->get('/admin/tenants');

        $response->assertOk();
        $response->assertSee('Riverside School');
        $response->assertSee('Acme University');
    }

    public function test_approving_a_pending_tenant_makes_it_reachable_and_lets_its_user_log_in(): void
    {
        $admin = $this->centralAdmin();
        $tenant = Tenant::create(['name' => 'Riverside School', 'slug' => 'riverside', 'status' => TenantStatus::Pending]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'jamie@riverside.test',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin)
            ->post('/admin/tenants/riverside/approve')
            ->assertRedirect();

        $this->assertTrue($tenant->fresh()->isActive());

        // The admin's session was forced via actingAs(), which (unlike a real
        // browser) doesn't re-derive the authenticated user from the session
        // store on each request. Log out explicitly so the following login
        // attempt exercises the real guest/auth flow instead of inheriting
        // the admin's still-cached guard state.
        $this->post('/logout');

        $login = $this->post('/t/riverside/login', [
            'email' => 'jamie@riverside.test',
            'password' => 'password',
        ]);

        $login->assertRedirect();
        $this->assertAuthenticatedAs($owner);
    }

    public function test_rejecting_a_pending_tenant_leaves_it_unreachable(): void
    {
        $admin = $this->centralAdmin();
        $tenant = Tenant::create(['name' => 'Riverside School', 'slug' => 'riverside', 'status' => TenantStatus::Pending]);

        $this->actingAs($admin)
            ->post('/admin/tenants/riverside/reject')
            ->assertRedirect();

        $this->assertSame(TenantStatus::Rejected, $tenant->fresh()->status);
        $this->get('/t/riverside')->assertNotFound();
    }

    public function test_an_already_active_tenant_cannot_be_approved_again(): void
    {
        $admin = $this->centralAdmin();
        Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);

        $this->actingAs($admin)
            ->post('/admin/tenants/acme/approve')
            ->assertNotFound();
    }
}
