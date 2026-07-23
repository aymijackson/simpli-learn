<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantManagementTest extends TestCase
{
    use RefreshDatabase;

    private function centralAdmin(): User
    {
        return User::factory()->create(['tenant_id' => null]);
    }

    public function test_the_tenant_detail_page_shows_its_users_and_packages(): void
    {
        $admin = $this->centralAdmin();
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Jamie Owner']);

        $response = $this->actingAs($admin)->get('/admin/tenants/acme');

        $response->assertOk();
        $response->assertSee('Jamie Owner');
        $response->assertSee('Learning Management');
    }

    public function test_an_admin_can_change_which_packages_a_tenant_has(): void
    {
        $admin = $this->centralAdmin();
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);

        $this->actingAs($admin)->put('/admin/tenants/acme/modules', [
            'modules' => ['cbt', 'library'],
        ])->assertRedirect();

        $tenant->refresh();
        $this->assertFalse($tenant->hasModule(Module::Lms));
        $this->assertTrue($tenant->hasModule(Module::Cbt));
        $this->assertTrue($tenant->hasModule(Module::Library));
    }

    public function test_an_admin_can_suspend_and_reactivate_an_active_tenant(): void
    {
        $admin = $this->centralAdmin();
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);

        $this->actingAs($admin)->post('/admin/tenants/acme/suspend')->assertRedirect();
        $this->assertSame(TenantStatus::Suspended, $tenant->fresh()->status);
        $this->get('/t/acme')->assertNotFound();

        $this->actingAs($admin)->post('/admin/tenants/acme/reactivate')->assertRedirect();
        $this->assertTrue($tenant->fresh()->isActive());
    }

    public function test_a_pending_tenant_cannot_be_suspended(): void
    {
        $admin = $this->centralAdmin();
        Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Pending]);

        $this->actingAs($admin)->post('/admin/tenants/acme/suspend')->assertNotFound();
    }
}
