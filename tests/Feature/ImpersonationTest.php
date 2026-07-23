<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function centralAdmin(): User
    {
        return User::factory()->create(['tenant_id' => null]);
    }

    public function test_an_admin_can_manage_a_tenants_content_via_impersonation(): void
    {
        $admin = $this->centralAdmin();
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($admin)
            ->post('/admin/tenants/acme/impersonate')
            ->assertRedirect();

        $this->assertAuthenticatedAs($owner);

        // Now acting as the tenant's owner, admin can reach the same
        // management screens a real owner would use — no parallel admin-side
        // CRUD needed.
        $this->post('/t/acme/lms/manage/courses', [
            'title' => 'Impersonated Course',
            'slug' => 'impersonated-course',
        ])->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'tenant_id' => $tenant->id,
            'slug' => 'impersonated-course',
        ]);
    }

    public function test_stopping_impersonation_returns_to_the_original_admin_session(): void
    {
        $admin = $this->centralAdmin();
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($admin)->post('/admin/tenants/acme/impersonate');

        $this->post('/t/acme/impersonate/stop')->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_pending_tenant_cannot_be_impersonated(): void
    {
        $admin = $this->centralAdmin();
        Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Pending]);

        $this->actingAs($admin)
            ->post('/admin/tenants/acme/impersonate')
            ->assertNotFound();
    }

    public function test_stopping_impersonation_without_having_started_it_is_forbidden(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)
            ->post('/t/acme/impersonate/stop')
            ->assertForbidden();
    }
}
