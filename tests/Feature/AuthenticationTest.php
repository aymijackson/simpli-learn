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

    public function test_a_workspace_member_logging_in_at_the_root_domain_is_taken_to_their_workspace(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@acme.test',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', ['email' => 'user@acme.test', 'password' => 'password'])
            ->assertRedirect(route('tenant.home', 'acme'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login', 'user_id' => $user->id, 'tenant_id' => $tenant->id]);
    }

    public function test_a_wrong_password_at_the_root_domain_reveals_nothing(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@acme.test',
            'password' => bcrypt('password'),
        ]);

        $this->from('/login')->post('/login', ['email' => 'user@acme.test', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
        $this->get('/login')->assertDontSee('Acme');
    }

    public function test_repeated_failed_logins_are_locked_out(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@acme.test',
            'password' => bcrypt('password'),
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/t/acme/login', ['email' => 'user@acme.test', 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        // Even the right password is refused while locked out.
        $this->post('/t/acme/login', ['email' => 'user@acme.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Too many login attempts. Please try again in 5 minutes.']);
        $this->assertGuest();
    }

    public function test_a_successful_login_resets_the_failed_attempt_count(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@acme.test',
            'password' => bcrypt('password'),
        ]);

        foreach (range(1, 4) as $attempt) {
            $this->post('/t/acme/login', ['email' => 'user@acme.test', 'password' => 'wrong']);
        }

        $this->post('/t/acme/login', ['email' => 'user@acme.test', 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
        $this->post('/t/acme/logout');

        $this->post('/t/acme/login', ['email' => 'user@acme.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        foreach (range(1, 6) as $attempt) {
            $this->post('/forgot-password', ['email' => "someone{$attempt}@example.test"]);
        }

        $this->post('/forgot-password', ['email' => 'someone7@example.test'])->assertStatus(429);
    }
}
