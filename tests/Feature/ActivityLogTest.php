<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner, 'name' => ucfirst($slug).' Owner', 'password' => 'password']);

        return [$tenant, $owner];
    }

    public function test_sign_ins_and_failed_sign_ins_are_recorded_against_the_workspace(): void
    {
        [$tenant, $owner] = $this->workspace();

        $this->post('/t/acme/login', ['email' => $owner->email, 'password' => 'wrong']);
        $this->post('/t/acme/login', ['email' => 'nobody@acme.test', 'password' => 'x']);
        $this->post('/t/acme/login', ['email' => $owner->email, 'password' => 'password']);

        $this->assertDatabaseHas('activity_logs', ['tenant_id' => $tenant->id, 'action' => 'auth.failed', 'user_id' => $owner->id]);
        $this->assertDatabaseHas('activity_logs', ['tenant_id' => $tenant->id, 'action' => 'auth.failed', 'description' => 'Failed sign-in for unknown email nobody@acme.test']);
        $this->assertDatabaseHas('activity_logs', ['tenant_id' => $tenant->id, 'action' => 'auth.login', 'user_id' => $owner->id, 'actor_name' => 'Acme Owner']);
    }

    public function test_team_changes_are_recorded_with_who_did_them(): void
    {
        Notification::fake();
        [$tenant, $owner] = $this->workspace();

        $this->actingAs($owner)->post('/t/acme/team', [
            'name' => 'Jordan Member', 'email' => 'jordan@acme.test', 'role' => 'member',
            'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
        ]);

        $entry = ActivityLog::where('action', 'team.member_added')->first();
        $this->assertSame($tenant->id, $entry->tenant_id);
        $this->assertSame('Acme Owner', $entry->actor_name);
        $this->assertSame('Added Jordan Member (jordan@acme.test) as Member', $entry->description);
    }

    public function test_owners_see_only_their_own_workspaces_log(): void
    {
        [$acme, $acmeOwner] = $this->workspace('acme');
        [$beta] = $this->workspace('beta');

        ActivityLog::record('settings.certificates', 'Acme changed certificate settings', tenantId: $acme->id);
        ActivityLog::record('settings.certificates', 'Beta changed certificate settings', tenantId: $beta->id);

        $this->actingAs($acmeOwner)->get('/t/acme/manage/activity')
            ->assertOk()
            ->assertSee('Acme changed certificate settings')
            ->assertDontSee('Beta changed certificate settings');
    }

    public function test_members_cannot_see_the_activity_log(): void
    {
        [$tenant] = $this->workspace();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($member)->get('/t/acme/manage/activity')->assertForbidden();
    }

    public function test_central_admins_see_every_workspace_and_can_filter(): void
    {
        [$acme] = $this->workspace('acme');
        [$beta] = $this->workspace('beta');
        $admin = User::factory()->create(['tenant_id' => null]);

        ActivityLog::record('payments.confirmed', 'Confirmed course payment REF-1', tenantId: $acme->id);
        ActivityLog::record('team.member_added', 'Added someone to Beta', tenantId: $beta->id);

        $this->actingAs($admin)->get('/admin/activity')
            ->assertOk()->assertSee('Confirmed course payment REF-1')->assertSee('Added someone to Beta');

        $this->actingAs($admin)->get('/admin/activity?category=payments')
            ->assertSee('Confirmed course payment REF-1')->assertDontSee('Added someone to Beta');
    }

    public function test_approving_a_workspace_is_recorded(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'Riverside', 'slug' => 'riverside', 'status' => TenantStatus::Pending]);
        $admin = User::factory()->create(['tenant_id' => null, 'name' => 'Platform Admin']);

        $this->actingAs($admin)->post('/admin/tenants/riverside/approve');

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id, 'action' => 'workspace.approved', 'actor_name' => 'Platform Admin', 'description' => 'Approved workspace Riverside',
        ]);
    }

    public function test_old_entries_are_pruned_after_the_retention_period(): void
    {
        ActivityLog::record('auth.login', 'Recent');
        ActivityLog::record('auth.login', 'Ancient')->forceFill(['created_at' => now()->subDays(ActivityLog::RETENTION_DAYS + 1)])->save();

        $this->artisan('model:prune', ['--model' => ActivityLog::class]);

        $this->assertDatabaseHas('activity_logs', ['description' => 'Recent']);
        $this->assertDatabaseMissing('activity_logs', ['description' => 'Ancient']);
    }
}
