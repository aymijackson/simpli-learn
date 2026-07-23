<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_central_admin_can_request_a_reset_link_and_it_points_at_the_central_reset_route(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['tenant_id' => null, 'email' => 'admin@example.test']);

        $this->post('/forgot-password', ['email' => 'admin@example.test'])->assertRedirect();

        Notification::assertSentTo($admin, ResetPassword::class, function (ResetPassword $notification) use ($admin) {
            $url = $notification->toMail($admin)->actionUrl;

            return str_contains($url, '/reset-password/') && ! str_contains($url, '/t/');
        });
    }

    public function test_a_tenant_user_can_request_a_reset_link_and_it_points_at_their_tenants_reset_route(): void
    {
        Notification::fake();

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'user@acme.test']);

        $this->post('/t/acme/forgot-password', ['email' => 'user@acme.test'])->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, '/t/acme/reset-password/');
        });
    }

    public function test_a_tenant_user_can_reset_their_password_end_to_end(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'user@acme.test']);

        $token = Password::broker('users')->createToken($user);

        $response = $this->post('/t/acme/reset-password', [
            'token' => $token,
            'email' => 'user@acme.test',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/t/acme/login');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password', $user->fresh()->password));

        $this->post('/t/acme/login', ['email' => 'user@acme.test', 'password' => 'new-password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'user@acme.test']);

        $response = $this->post('/t/acme/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'user@acme.test',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('new-password', $user->fresh()->password));
    }
}
