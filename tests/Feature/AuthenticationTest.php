<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_log_in_at_their_tenant_url(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@acme.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/t/acme/login', [
            'email' => 'user@acme.test',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_tenants_user_cannot_log_in_at_a_different_tenants_url(): void
    {
        $acme = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);

        User::factory()->create([
            'tenant_id' => $acme->id,
            'email' => 'user@acme.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/t/other/login', [
            'email' => 'user@acme.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_central_admin_can_log_in_at_the_root_domain(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'email' => 'admin@example.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_tenant_user_cannot_log_in_at_the_root_domain(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@acme.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@acme.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
