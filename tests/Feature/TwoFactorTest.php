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

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);

        return User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'ada@acme.test', 'password' => 'password'] + $attributes);
    }

    private function withTwoFactor(User $user): User
    {
        $user->forceFill([
            'two_factor_secret' => TwoFactor::generateSecret(),
            'two_factor_recovery_codes' => ['aaaaa-bbbbb', 'ccccc-ddddd'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    public function test_totp_codes_match_the_rfc_6238_test_vectors(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // "12345678901234567890"

        $this->assertSame('287082', TwoFactor::codeAt($secret, 59));
        $this->assertSame('081804', TwoFactor::codeAt($secret, 1111111109));
        $this->assertSame('279037', TwoFactor::codeAt($secret, 2000000000));
    }

    public function test_a_user_can_set_up_two_step_login(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/t/acme/profile/two-factor')->assertRedirect('/t/acme/profile#two-factor');
        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->get('/t/acme/profile')->assertSee('Enter this key instead', false);

        $this->actingAs($user)->post('/t/acme/profile/two-factor/confirm', ['code' => TwoFactor::codeAt($secret, time())])
            ->assertSessionHas('recovery_codes');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
        $this->assertDatabaseHas('activity_logs', ['action' => 'security.two_factor_enabled', 'user_id' => $user->id]);
    }

    public function test_setup_is_not_turned_on_by_a_wrong_code(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post('/t/acme/profile/two-factor');

        $this->actingAs($user)->post('/t/acme/profile/two-factor/confirm', ['code' => '000000'])
            ->assertSessionHasErrorsIn('twoFactor', 'code');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_sign_in_requires_the_code_when_two_step_login_is_on(): void
    {
        $user = $this->withTwoFactor($this->user());

        $this->post('/t/acme/login', ['email' => 'ada@acme.test', 'password' => 'password'])
            ->assertRedirect('/t/acme/two-factor-challenge');
        $this->assertGuest();

        $this->get('/t/acme/two-factor-challenge')->assertOk()->assertSee('Two-step verification');

        $this->post('/t/acme/two-factor-challenge', ['code' => TwoFactor::codeAt($user->two_factor_secret, time())])
            ->assertRedirect('/t/acme');
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_code_does_not_sign_in(): void
    {
        $this->withTwoFactor($this->user());

        $this->post('/t/acme/login', ['email' => 'ada@acme.test', 'password' => 'password']);
        $this->post('/t/acme/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.failed', 'description' => 'Failed sign-in (wrong two-step code)']);
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $user = $this->withTwoFactor($this->user());
        $code = TwoFactor::codeAt($user->two_factor_secret, time());

        $this->assertTrue(TwoFactor::verify($user, $user->two_factor_secret, $code));
        $this->assertFalse(TwoFactor::verify($user, $user->two_factor_secret, $code));
    }

    public function test_a_recovery_code_works_once(): void
    {
        $user = $this->withTwoFactor($this->user());

        $this->post('/t/acme/login', ['email' => 'ada@acme.test', 'password' => 'password']);
        $this->post('/t/acme/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB'])->assertRedirect('/t/acme/profile');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['ccccc-ddddd'], $user->fresh()->two_factor_recovery_codes);

        auth()->logout();
        $this->post('/t/acme/login', ['email' => 'ada@acme.test', 'password' => 'password']);
        $this->post('/t/acme/two-factor-challenge', ['recovery_code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_the_challenge_cannot_be_reached_without_a_password_first(): void
    {
        $this->user();

        $this->get('/t/acme/two-factor-challenge')->assertRedirect('/t/acme/login');
    }

    public function test_turning_it_off_requires_the_password(): void
    {
        $user = $this->withTwoFactor($this->user());

        $this->actingAs($user)->delete('/t/acme/profile/two-factor', ['password' => 'wrong'])
            ->assertSessionHasErrorsIn('twoFactor', 'password');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->delete('/t/acme/profile/two-factor', ['password' => 'password']);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_an_owner_can_reset_a_members_two_step_login(): void
    {
        $member = $this->withTwoFactor($this->user());
        $owner = User::factory()->create(['tenant_id' => $member->tenant_id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)->post("/t/acme/team/{$member->id}/two-factor/reset")->assertRedirect('/t/acme/team');

        $this->assertFalse($member->fresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('activity_logs', ['action' => 'security.two_factor_reset', 'user_id' => $owner->id]);
    }

    public function test_central_admins_get_the_challenge_on_the_central_site(): void
    {
        $admin = $this->withTwoFactor(User::factory()->create(['tenant_id' => null, 'email' => 'admin@example.test', 'password' => 'password']));

        $this->post('/login', ['email' => 'admin@example.test', 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->post('/two-factor-challenge', ['code' => TwoFactor::codeAt($admin->two_factor_secret, time())])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_the_secret_is_encrypted_at_rest(): void
    {
        $user = $this->withTwoFactor($this->user());

        $raw = \DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame($user->two_factor_secret, $raw);
    }
}
