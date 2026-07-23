<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_only_enabled_modules_for_the_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/t/acme');

        $response->assertOk();
        $response->assertSee('Acme');
        $response->assertSee('/t/acme/cbt', false);
        $response->assertDontSee('/t/acme/lms', false);
    }

    public function test_unknown_tenant_slug_returns_404(): void
    {
        $this->get('/t/nosuchtenant')->assertNotFound();
    }

    public function test_pending_tenant_returns_404(): void
    {
        Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Pending]);

        $this->get('/t/acme')->assertNotFound();
    }

    public function test_rejected_tenant_returns_404(): void
    {
        Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Rejected]);

        $this->get('/t/acme')->assertNotFound();
    }

    public function test_guests_are_redirected_away_from_the_home_page(): void
    {
        Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);

        $this->get('/t/acme')->assertRedirect();
        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_their_own_tenants_login_not_the_central_login(): void
    {
        Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);

        $this->get('/t/acme')->assertRedirect('/t/acme/login');
    }

    public function test_an_authenticated_tenant_user_visiting_their_login_page_is_sent_to_their_home_not_the_marketing_page(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/t/acme/login')->assertRedirect('/t/acme');
    }

    public function test_an_authenticated_central_admin_visiting_login_is_sent_to_the_admin_dashboard_not_the_marketing_page(): void
    {
        $admin = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)->get('/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_module_not_enabled_for_tenant_returns_403(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/t/acme/lms')->assertForbidden();
    }

    public function test_module_enabled_for_tenant_is_reachable(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/t/acme/cbt')->assertOk();
    }

    public function test_disabled_module_is_not_reachable_even_if_the_row_exists(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => false]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/t/acme/cbt')->assertForbidden();
    }
}
