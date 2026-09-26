<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function tenantUser(array $attributes = []): User
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);

        return User::factory()->create(['tenant_id' => $tenant->id, 'password' => 'old-password-123'] + $attributes);
    }

    public function test_a_tenant_user_can_view_their_profile(): void
    {
        $user = $this->tenantUser(['name' => 'Ada Obi']);

        $this->actingAs($user)->get('/t/acme/profile')->assertOk()->assertSee('Profile &amp; security', false)->assertSee('Ada Obi');
    }

    public function test_a_central_admin_can_view_their_profile(): void
    {
        $admin = User::factory()->create(['tenant_id' => null, 'name' => 'Platform Admin']);

        $this->actingAs($admin)->get('/profile')->assertOk()->assertSee('Platform Admin');
    }

    public function test_guests_cannot_reach_the_profile(): void
    {
        $this->tenantUser();

        $this->get('/t/acme/profile')->assertRedirect('/t/acme/login');
    }

    public function test_a_user_can_update_their_name_and_email(): void
    {
        $user = $this->tenantUser();

        $this->actingAs($user)
            ->put('/t/acme/profile', ['name' => 'New Name', 'email' => 'new@acme.test'])
            ->assertRedirect('/t/acme/profile');

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('new@acme.test', $user->fresh()->email);
    }

    public function test_email_must_be_unique_within_the_workspace_only(): void
    {
        $user = $this->tenantUser();
        User::factory()->create(['tenant_id' => $user->tenant_id, 'email' => 'taken@acme.test']);

        // Another workspace using the same address doesn't matter.
        $other = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        User::factory()->create(['tenant_id' => $other->id, 'email' => 'shared@example.test']);

        $this->actingAs($user)
            ->put('/t/acme/profile', ['name' => 'X', 'email' => 'taken@acme.test'])
            ->assertSessionHasErrorsIn('details', 'email');

        $this->actingAs($user)
            ->put('/t/acme/profile', ['name' => 'X', 'email' => 'shared@example.test'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_user_can_change_their_password_with_the_current_one(): void
    {
        $user = $this->tenantUser();

        $this->actingAs($user)->put('/t/acme/profile/password', [
            'current_password' => 'old-password-123',
            'password' => 'mango-radio-lantern-seventy',
            'password_confirmation' => 'mango-radio-lantern-seventy',
        ])->assertRedirect('/t/acme/profile')->assertSessionHas('status', 'Your password has been changed.');

        $this->assertTrue(Hash::check('mango-radio-lantern-seventy', $user->fresh()->password));
    }

    public function test_changing_password_requires_the_correct_current_password(): void
    {
        $user = $this->tenantUser();

        $this->actingAs($user)->put('/t/acme/profile/password', [
            'current_password' => 'wrong',
            'password' => 'mango-radio-lantern-seventy',
            'password_confirmation' => 'mango-radio-lantern-seventy',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }
}
