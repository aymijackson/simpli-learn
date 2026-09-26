<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequireTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['security.require_two_factor' => true]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
    }

    private function withTwoFactor(User $user): User
    {
        $user->forceFill(['two_factor_secret' => TwoFactor::generateSecret(), 'two_factor_confirmed_at' => now()])->save();

        return $user;
    }

    private function user(UserRole $role): User
    {
        return User::factory()->create(['tenant_id' => Tenant::first()->id, 'role' => $role]);
    }

    public function test_an_owner_without_two_step_login_is_sent_to_set_it_up(): void
    {
        $owner = $this->user(UserRole::Owner);

        foreach (['/t/acme/manage', '/t/acme/team', '/t/acme/lms/manage/courses'] as $path) {
            $this->actingAs($owner)->get($path)->assertRedirect('/t/acme/profile#two-factor');
        }

        $this->actingAs($owner)->get('/t/acme/profile')->assertOk();
    }

    public function test_an_owner_with_two_step_login_can_manage(): void
    {
        $owner = $this->withTwoFactor($this->user(UserRole::Owner));

        $this->actingAs($owner)->get('/t/acme/manage')->assertOk();
    }

    public function test_members_are_never_forced(): void
    {
        $member = $this->user(UserRole::Member);

        $this->actingAs($member)->get('/t/acme')->assertOk();
        $this->actingAs($member)->get('/t/acme/lms')->assertOk();
    }

    public function test_platform_admins_must_set_it_up_too(): void
    {
        $admin = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)->get('/admin')->assertRedirect('/profile#two-factor');
        $this->actingAs($admin)->get('/profile')->assertOk();

        $this->withTwoFactor($admin);
        $this->actingAs($admin->fresh())->get('/admin')->assertOk();
    }

    public function test_it_is_skipped_while_an_admin_is_impersonating(): void
    {
        $owner = $this->user(UserRole::Owner);

        $this->actingAs($owner)->withSession(['impersonator_id' => 999])->get('/t/acme/manage')->assertOk();
    }

    public function test_it_can_be_switched_off(): void
    {
        config(['security.require_two_factor' => false]);

        $this->actingAs($this->user(UserRole::Owner))->get('/t/acme/manage')->assertOk();
    }
}
