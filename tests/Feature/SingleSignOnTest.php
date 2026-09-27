<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SingleSignOnTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.google.client_id' => 'google-id', 'services.google.client_secret' => 'google-secret',
            'services.microsoft.client_id' => 'ms-id', 'services.microsoft.client_secret' => 'ms-secret',
        ]);
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active, 'sso_providers' => ['google', 'microsoft']]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
    }

    /** Pretend Google/Microsoft signed this person in. */
    private function providerReturns(string $provider, string $email, bool $verified = true, string $name = 'Ada Lovelace'): void
    {
        $account = (new SocialiteUser)->setRaw(['email_verified' => $verified])->map(['id' => '123', 'name' => $name, 'email' => $email]);
        $driver = Mockery::mock();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($account);
        $driver->shouldReceive('redirect')->andReturn(redirect('https://accounts.example.test/authorize'));
        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    private function signIn(string $provider = 'google')
    {
        $this->get("/t/acme/auth/{$provider}")->assertRedirect('https://accounts.example.test/authorize');

        return $this->get("/auth/{$provider}/callback");
    }

    public function test_the_sign_in_page_shows_only_the_enabled_providers(): void
    {
        $this->get('/t/acme/login')->assertSee('Continue with Google')->assertSee('Continue with Microsoft');

        $this->tenant->update(['sso_providers' => ['microsoft']]);
        $this->get('/t/acme/login')->assertDontSee('Continue with Google')->assertSee('Continue with Microsoft');
        $this->get('/t/acme/auth/google')->assertNotFound();

        // Not configured for the platform = not offered, whatever the workspace says.
        config(['services.microsoft.client_id' => null]);
        $this->get('/t/acme/login')->assertDontSee('Continue with Microsoft');
    }

    public function test_existing_members_sign_in_with_google(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'ada@acme.com']);
        $this->providerReturns('google', 'ADA@acme.com');

        $this->signIn()->assertRedirect(route('tenant.home', 'acme'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_people_are_refused_unless_their_domain_may_join(): void
    {
        $this->providerReturns('microsoft', 'new.person@acme.com');
        $this->signIn('microsoft')->assertRedirect(route('tenant.login', 'acme'))->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->tenant->update(['sso_domains' => ['acme.com']]);
        $this->signIn('microsoft')->assertRedirect(route('tenant.home', 'acme'));
        $joined = User::withoutGlobalScopes()->where('email', 'new.person@acme.com')->firstOrFail();
        $this->assertSame($this->tenant->id, $joined->tenant_id);
        $this->assertSame(UserRole::Member, $joined->role);
        $this->assertNotNull($joined->email_verified_at);
        $this->assertAuthenticatedAs($joined);
    }

    public function test_other_domains_and_unverified_google_emails_are_refused(): void
    {
        $this->tenant->update(['sso_domains' => ['acme.com']]);

        $this->providerReturns('google', 'someone@evil-acme.com');
        $this->signIn()->assertSessionHasErrors('email');

        User::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'ada@acme.com']);
        $this->providerReturns('google', 'ada@acme.com', verified: false);
        $this->signIn()->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_account_in_another_workspace_does_not_sign_in_here(): void
    {
        $other = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        User::factory()->create(['tenant_id' => $other->id, 'email' => 'ada@acme.com']);
        $this->providerReturns('google', 'ada@acme.com');

        $this->signIn()->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_people_and_two_step_login_are_respected(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'ada@acme.com', 'deactivated_at' => now()]);
        $this->providerReturns('google', 'ada@acme.com');
        $this->signIn()->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->forceFill(['deactivated_at' => null, 'two_factor_secret' => encrypt('SECRET'), 'two_factor_confirmed_at' => now()])->save();
        $this->signIn()->assertRedirect(route('tenant.two-factor.challenge', 'acme'));
        $this->assertGuest();
        $this->assertSame($user->id, session('two_factor.login.user_id'));
    }

    public function test_a_cancelled_sign_in_goes_back_to_the_login_page(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('redirectUrl')->andReturnSelf();
        $driver->shouldReceive('redirect')->andReturn(redirect('https://accounts.example.test/authorize'));
        $driver->shouldReceive('user')->andThrow(new \Laravel\Socialite\Two\InvalidStateException);
        Socialite::shouldReceive('driver')->andReturn($driver);

        $this->signIn()->assertRedirect(route('tenant.login', 'acme'))->assertSessionHasErrors('email');
    }

    public function test_owners_choose_providers_and_join_domains(): void
    {
        $owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)->put('/t/acme/settings', ['name' => 'Acme', 'sso_providers' => ['google'], 'sso_domains' => '@Acme.com, acme.co.uk'])
            ->assertSessionHasNoErrors();
        $this->tenant->refresh();
        $this->assertSame(['google'], $this->tenant->sso_providers);
        $this->assertSame(['acme.com', 'acme.co.uk'], $this->tenant->sso_domains);

        $this->actingAs($owner)->put('/t/acme/settings', ['name' => 'Acme', 'sso_domains' => 'not a domain'])->assertSessionHasErrors('sso_domains');
        $this->actingAs($owner)->put('/t/acme/settings', ['name' => 'Acme', 'sso_providers' => ['facebook']])->assertSessionHasErrors('sso_providers.0');
    }
}
