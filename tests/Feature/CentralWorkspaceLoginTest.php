<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\YourWorkspaces;
use App\Support\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Workspace members who don't remember their workspace's address can log in
 * on the central page, or have their workspaces emailed to them.
 */
class CentralWorkspaceLoginTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(string $slug, string $name, TenantStatus $status = TenantStatus::Active): Tenant
    {
        $tenant = Tenant::create(['name' => $name, 'slug' => $slug, 'status' => $status]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);

        return $tenant;
    }

    private function member(Tenant $tenant, string $password = 'secret-pass', array $attributes = []): User
    {
        return User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'ada@example.test', 'password' => $password] + $attributes);
    }

    public function test_the_member_lands_in_their_workspace_and_stays_signed_in_there(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $ada = $this->member($acme);

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('tenant.home', 'acme'));

        $this->assertAuthenticatedAs($ada);
        $this->get('/t/acme')->assertOk()->assertSee('Acme Academy');
    }

    public function test_a_password_that_matches_only_one_workspace_goes_straight_there(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $beta = $this->workspace('beta', 'Beta College');
        $this->member($acme, 'other-pass');
        $ada = $this->member($beta);

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('tenant.home', 'beta'));

        $this->assertAuthenticatedAs($ada);
    }

    public function test_the_member_chooses_when_their_password_works_in_several_workspaces(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $beta = $this->workspace('beta', 'Beta College');
        $this->workspace('gamma', 'Gamma School');
        $this->member($acme);
        $atBeta = $this->member($beta);

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('login.workspace'));
        $this->assertGuest();

        $this->get('/login/workspace')
            ->assertOk()
            ->assertSee('Acme Academy')
            ->assertSee('Beta College')
            ->assertDontSee('Gamma School');

        $this->post('/login/workspace', ['account' => $atBeta->id])
            ->assertRedirect(route('tenant.home', 'beta'));

        $this->assertAuthenticatedAs($atBeta);
    }

    public function test_only_offered_accounts_can_be_chosen(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $beta = $this->workspace('beta', 'Beta College');
        $this->member($acme);
        $this->member($beta);
        $someoneElse = User::factory()->create(['tenant_id' => $acme->id, 'email' => 'eve@example.test', 'password' => 'secret-pass']);

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass']);

        $this->post('/login/workspace', ['account' => $someoneElse->id])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_the_choice_expires(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $beta = $this->workspace('beta', 'Beta College');
        $atAcme = $this->member($acme);
        $this->member($beta);

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass']);
        $this->travel(6)->minutes();

        $this->get('/login/workspace')->assertRedirect(route('login'));
        $this->post('/login/workspace', ['account' => $atAcme->id])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_two_step_login_is_still_required(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $ada = $this->member($acme);
        $secret = TwoFactor::generateSecret();
        $ada->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => ['aaaaa-bbbbb'], 'two_factor_confirmed_at' => now()])->save();

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('tenant.two-factor.challenge', 'acme'));
        $this->assertGuest();

        $this->post('/t/acme/two-factor-challenge', ['code' => TwoFactor::codeAt($secret, time())])
            ->assertRedirect(route('tenant.home', 'acme'));
        $this->assertAuthenticatedAs($ada);
    }

    public function test_deactivated_members_are_still_refused(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $this->member($acme, attributes: ['deactivated_at' => now()]);

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass'])
            ->assertSessionHasErrors(['email' => 'This account has been deactivated. Contact your workspace administrator.']);
        $this->assertGuest();
    }

    public function test_inactive_workspaces_are_ignored(): void
    {
        $suspended = $this->workspace('acme', 'Acme Academy', TenantStatus::Suspended);
        $this->member($suspended);

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
        $this->assertGuest();
    }

    public function test_a_remembered_admin_page_is_not_where_members_end_up(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $this->member($acme);

        $this->get('/admin')->assertRedirect();

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('tenant.home', 'acme'));
    }

    public function test_central_admins_are_unaffected(): void
    {
        $admin = User::factory()->create(['tenant_id' => null, 'email' => 'admin@example.test', 'password' => 'secret-pass']);

        $this->post('/login', ['email' => 'admin@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_failed_attempts_still_lock_the_central_page(): void
    {
        $acme = $this->workspace('acme', 'Acme Academy');
        $this->member($acme);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => 'ada@example.test', 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => 'ada@example.test', 'password' => 'secret-pass'])
            ->assertSessionHasErrors(['email' => 'Too many login attempts. Please try again in 5 minutes.']);
        $this->assertGuest();
    }

    public function test_the_central_login_page_offers_the_workspace_reminder(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('we&#039;ll take you to your workspace', false)
            ->assertSee(route('workspace.forgot'));

        $this->workspace('acme', 'Acme Academy');
        $this->get('/t/acme/login')->assertOk()->assertDontSee(route('workspace.forgot'));
    }

    public function test_forgot_workspace_emails_links_to_each_active_workspace(): void
    {
        Notification::fake();
        $acme = $this->workspace('acme', 'Acme Academy');
        $beta = $this->workspace('beta', 'Beta College');
        $gone = $this->workspace('gone', 'Gone School', TenantStatus::Suspended);
        $old = $this->workspace('old', 'Old Institute');
        $this->member($acme);
        $this->member($beta);
        $this->member($gone);
        $this->member($old, attributes: ['deactivated_at' => now()]);

        $this->get('/forgot-workspace')->assertOk();
        $this->from('/forgot-workspace')->post('/forgot-workspace', ['email' => 'ada@example.test'])
            ->assertRedirect('/forgot-workspace')
            ->assertSessionHas('status');

        Notification::assertSentOnDemand(YourWorkspaces::class, function (YourWorkspaces $notification, array $channels, AnonymousNotifiable $notifiable) {
            $mail = $notification->toMail($notifiable)->render();

            return $notifiable->routes['mail'] === 'ada@example.test'
                && $notification->workspaces->pluck('slug')->all() === ['acme', 'beta']
                && str_contains($mail, route('tenant.login', 'acme'))
                && str_contains($mail, route('tenant.login', 'beta'));
        });
    }

    public function test_forgot_workspace_answers_the_same_for_unknown_emails(): void
    {
        Notification::fake();

        $this->from('/forgot-workspace')->post('/forgot-workspace', ['email' => 'nobody@example.test'])
            ->assertSessionHas('status', "If that email belongs to any workspaces, we've sent you a link to each one. Check your inbox (and spam folder).");

        Notification::assertNothingSent();
    }

    public function test_password_fields_have_a_show_password_button(): void
    {
        $this->workspace('acme', 'Acme Academy');

        foreach (['/login', '/t/acme/login', '/signup'] as $page) {
            $this->get($page)->assertOk()
                ->assertSee('data-password-toggle', false)
                ->assertSee('aria-label="Show password"', false);
        }
    }
}
