<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AddedToWorkspace;
use App\Notifications\NewWorkspaceSignup;
use App\Notifications\WorkspaceApproved;
use App\Notifications\WorkspaceRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class NotificationEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function pendingWorkspace(): array
    {
        $tenant = Tenant::create(['name' => 'Riverside School', 'slug' => 'riverside', 'status' => TenantStatus::Pending]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        return [$tenant, $owner, $member];
    }

    public function test_approving_a_workspace_emails_its_owner_only(): void
    {
        Notification::fake();
        [$tenant, $owner, $member] = $this->pendingWorkspace();
        $admin = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)->post('/admin/tenants/riverside/approve')
            ->assertSessionHas('status', 'Riverside School has been approved.');

        Notification::assertSentTo($owner, WorkspaceApproved::class, function ($notification) use ($owner) {
            $mail = $notification->toMail($owner);

            return str_contains($mail->actionUrl, '/t/riverside/login');
        });
        Notification::assertNotSentTo($member, WorkspaceApproved::class);
    }

    public function test_rejecting_a_workspace_emails_its_owner(): void
    {
        Notification::fake();
        [$tenant, $owner] = $this->pendingWorkspace();

        $this->actingAs(User::factory()->create(['tenant_id' => null]))->post('/admin/tenants/riverside/reject');

        Notification::assertSentTo($owner, WorkspaceRejected::class);
    }

    public function test_a_mail_failure_does_not_block_approval(): void
    {
        [$tenant] = $this->pendingWorkspace();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('SMTP server unreachable'));

        $this->actingAs(User::factory()->create(['tenant_id' => null]))
            ->post('/admin/tenants/riverside/approve')
            ->assertSessionHas('status', 'Riverside School has been approved. (The owner could not be emailed — check the mail settings.)');

        $this->assertTrue($tenant->fresh()->isActive());
    }

    public function test_a_new_signup_emails_the_central_admins(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['tenant_id' => null]);

        $this->post('/signup', [
            'organization_name' => 'Riverside School',
            'slug' => 'riverside',
            'name' => 'Rita Owner',
            'email' => 'rita@riverside.test',
            'password' => 'mango-radio-lantern-seventy',
            'password_confirmation' => 'mango-radio-lantern-seventy',
            'modules' => ['lms'],
        ])->assertRedirect(route('signup.pending'));

        Notification::assertSentTo($admin, NewWorkspaceSignup::class, fn ($notification) => $notification->ownerEmail === 'rita@riverside.test');
    }

    public function test_adding_a_team_member_sends_them_a_welcome_email_without_the_password(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner, 'name' => 'Olu Owner']);

        $this->actingAs($owner)->post('/t/acme/team', [
            'name' => 'Jordan Member',
            'email' => 'jordan@acme.test',
            'role' => 'member',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHas('status', "Team member added. We've emailed jordan@acme.test their login link.");

        $member = User::withoutGlobalScopes()->where('email', 'jordan@acme.test')->first();

        Notification::assertSentTo($member, AddedToWorkspace::class, function ($notification) use ($member) {
            $mail = $notification->toMail($member);
            $text = implode(' ', array_merge($mail->introLines, $mail->outroLines));

            return $notification->addedBy === 'Olu Owner'
                && str_contains($mail->actionUrl, '/t/acme/login')
                && ! str_contains($text, 'secret-pass-123');
        });
    }
}
