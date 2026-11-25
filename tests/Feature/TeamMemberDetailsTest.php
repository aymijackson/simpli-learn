<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MemberEmailChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Owners can correct a member's name, email and job title — and it's all logged. */
class TeamMemberDetailsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->tenant = Tenant::create(['name' => 'Acme Academy', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner, 'name' => 'Olu Owner']);
        $this->member = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Lerner', 'email' => 'ada@old.test', 'password' => 'secret-pass']);
    }

    private function save(array $details, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->owner)
            ->from("/t/acme/team/{$this->member->id}")
            ->put("/t/acme/team/{$this->member->id}/details", $details + ['name' => 'Ada Lerner', 'email' => 'ada@old.test', 'job_title' => '']);
    }

    public function test_the_member_page_has_the_details_form(): void
    {
        $this->actingAs($this->owner)->get("/t/acme/team/{$this->member->id}")
            ->assertOk()
            ->assertSee(route('tenant.team.details', $this->member), false)
            ->assertSee('value="ada@old.test"', false);
    }

    public function test_an_owner_can_change_name_email_and_job_title_and_it_is_logged(): void
    {
        $this->save(['name' => 'Ada Learner', 'email' => 'ada@new.test', 'job_title' => 'Accountant'])
            ->assertRedirect("/t/acme/team/{$this->member->id}")
            ->assertSessionHasNoErrors();

        $member = $this->member->fresh();
        $this->assertSame('Ada Learner', $member->name);
        $this->assertSame('ada@new.test', $member->email);
        $this->assertSame('Accountant', $member->jobTitle->name);

        $log = ActivityLog::withoutGlobalScopes()->where('action', 'team.details_changed')->sole();
        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertStringContainsString('email from "ada@old.test" to "ada@new.test"', $log->description);
        $this->assertStringContainsString('name from "Ada Lerner" to "Ada Learner"', $log->description);
        $this->assertSame(['from' => 'ada@old.test', 'to' => 'ada@new.test'], $log->properties['changes']['email']);
    }

    public function test_the_old_address_is_told_and_the_member_logs_in_with_the_new_one(): void
    {
        $this->save(['email' => 'ada@new.test']);

        Notification::assertSentOnDemand(MemberEmailChanged::class, function (MemberEmailChanged $notification, array $channels, AnonymousNotifiable $notifiable) {
            $mail = $notification->toMail($notifiable)->render();

            return $notifiable->routes['mail'] === 'ada@old.test'
                && str_contains($mail, 'ada@new.test')
                && str_contains($mail, 'Olu Owner');
        });

        auth()->logout();
        $this->post('/t/acme/login', ['email' => 'ada@old.test', 'password' => 'secret-pass'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/t/acme/login', ['email' => 'ada@new.test', 'password' => 'secret-pass']);
        $this->assertAuthenticatedAs($this->member->fresh());
    }

    public function test_changing_only_the_name_sends_no_email(): void
    {
        $this->save(['name' => 'Ada Learner'])->assertSessionHasNoErrors();

        Notification::assertNothingSent();
        $this->assertSame(1, ActivityLog::withoutGlobalScopes()->where('action', 'team.details_changed')->count());
    }

    public function test_saving_without_changes_logs_nothing(): void
    {
        $this->save([])->assertSessionHas('status', 'No changes to save.');

        $this->assertSame(0, ActivityLog::withoutGlobalScopes()->where('action', 'team.details_changed')->count());
    }

    public function test_the_email_must_be_free_in_this_workspace(): void
    {
        $this->save(['email' => $this->owner->email])->assertSessionHasErrorsIn('details', 'email');
        $this->assertSame('ada@old.test', $this->member->fresh()->email);
    }

    public function test_the_same_email_may_exist_in_another_workspace(): void
    {
        $other = Tenant::create(['name' => 'Beta', 'slug' => 'beta', 'status' => TenantStatus::Active]);
        User::factory()->create(['tenant_id' => $other->id, 'email' => 'ada@new.test']);

        $this->save(['email' => 'ada@new.test'])->assertSessionHasNoErrors();
        $this->assertSame('ada@new.test', $this->member->fresh()->email);
    }

    public function test_members_cannot_edit_each_other(): void
    {
        $colleague = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->save(['email' => 'hacked@evil.test'], $colleague)->assertForbidden();
        $this->assertSame('ada@old.test', $this->member->fresh()->email);
    }

    public function test_owners_cannot_edit_members_of_another_workspace(): void
    {
        $other = Tenant::create(['name' => 'Beta', 'slug' => 'beta', 'status' => TenantStatus::Active]);
        $outsider = User::factory()->create(['tenant_id' => $other->id, 'email' => 'out@beta.test']);

        $this->actingAs($this->owner)
            ->put("/t/acme/team/{$outsider->id}/details", ['name' => 'X', 'email' => 'x@x.test'])
            ->assertNotFound();
        $this->assertSame('out@beta.test', $outsider->fresh()->email);
    }
}
