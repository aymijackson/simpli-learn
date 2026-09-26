<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member, 'name' => 'Jordan Member']);

        return [$tenant, $owner, $member];
    }

    public function test_an_owner_can_view_a_members_profile_with_course_progress(): void
    {
        [$tenant, $owner, $member] = $this->workspace();
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Data Security Essentials', 'slug' => 'data-security', 'is_published' => true]);
        Enrollment::create(['tenant_id' => $tenant->id, 'course_id' => $course->id, 'user_id' => $member->id]);

        $this->actingAs($owner)->get("/t/acme/team/{$member->id}")
            ->assertOk()
            ->assertSee('Jordan Member')
            ->assertSee('Course progress')
            ->assertSee('Data Security Essentials')
            ->assertSee('Download their data');
    }

    public function test_the_team_list_links_to_each_profile(): void
    {
        [, $owner, $member] = $this->workspace();

        $this->actingAs($owner)->get('/t/acme/team')->assertSee("/t/acme/team/{$member->id}", false);
    }

    public function test_members_cannot_view_other_profiles(): void
    {
        [, $owner, $member] = $this->workspace();

        $this->actingAs($member)->get("/t/acme/team/{$owner->id}")->assertForbidden();
    }

    public function test_an_owner_cannot_view_someone_from_another_workspace(): void
    {
        [, $acmeOwner] = $this->workspace('acme');
        [, , $betaMember] = $this->workspace('beta');

        $this->actingAs($acmeOwner)->get("/t/acme/team/{$betaMember->id}")->assertNotFound();
    }

    public function test_the_mail_test_command_explains_when_mail_is_not_being_sent(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('mail:test', ['to' => 'someone@example.test'])
            ->expectsOutputToContain('MAIL_MAILER is "log" — emails are not sent at all.')
            ->assertExitCode(1);
    }

    public function test_the_mail_test_command_sends_a_test_email(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '', 'mail.from.address' => 'noreply@acme.test']);

        $this->artisan('mail:test', ['to' => 'someone@example.test'])
            ->expectsOutputToContain('Accepted by the mail server.')
            ->assertExitCode(0);
    }
}
