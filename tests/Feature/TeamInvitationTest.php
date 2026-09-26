<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AddedToWorkspace;
use App\Notifications\CourseAssigned;
use Elibrary\Lms\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner, 'name' => 'Olu Owner', 'email' => 'olu@acme.test']);
    }

    private function csv(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('people.csv', $contents);
    }

    private function user(string $email): ?User
    {
        return User::withoutGlobalScopes()->where('email', $email)->first();
    }

    public function test_adding_someone_without_a_password_emails_a_set_password_link(): void
    {
        $this->actingAs($this->owner)->post('/t/acme/team', ['name' => 'Ada Obi', 'email' => 'ada@acme.test', 'role' => 'member'])
            ->assertSessionHas('status', "Team member added. We've emailed ada@acme.test an invitation to set their password.");

        $ada = $this->user('ada@acme.test');
        Notification::assertSentTo($ada, AddedToWorkspace::class, function ($notification) use ($ada) {
            $mail = $notification->toMail($ada);

            return $notification->setPasswordUrl !== null
                && str_contains($mail->actionUrl, '/t/acme/reset-password/')
                && str_contains($mail->actionUrl, 'invite=1')
                && $mail->actionText === 'Set your password';
        });
    }

    public function test_the_invitation_link_sets_a_password_even_days_later(): void
    {
        $this->actingAs($this->owner)->post('/t/acme/team', ['name' => 'Ada Obi', 'email' => 'ada@acme.test', 'role' => 'member']);
        $ada = $this->user('ada@acme.test');

        $url = null;
        Notification::assertSentTo($ada, AddedToWorkspace::class, function ($notification) use (&$url) {
            $url = $notification->setPasswordUrl;

            return true;
        });
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $token = basename(parse_url($url, PHP_URL_PATH));

        auth()->logout();
        $this->travel(3)->days(); // an ordinary reset link would have expired after 60 minutes

        $this->get($url)->assertOk()->assertSee('Set your password');
        $this->post('/t/acme/reset-password', [
            'token' => $token, 'email' => 'ada@acme.test', 'invite' => 1,
            'password' => 'mango-radio-lantern-seventy', 'password_confirmation' => 'mango-radio-lantern-seventy',
        ])->assertRedirect('/t/acme/login')->assertSessionHas('status', 'Your password is set. Log in to get started.');

        $this->assertTrue(Hash::check('mango-radio-lantern-seventy', $ada->fresh()->password));
    }

    public function test_an_owner_can_still_set_a_password_for_someone(): void
    {
        $this->actingAs($this->owner)->post('/t/acme/team', [
            'name' => 'Tunde', 'email' => 'tunde@acme.test', 'role' => 'member',
            'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
        ]);

        $tunde = $this->user('tunde@acme.test');
        $this->assertTrue(Hash::check('secret-pass-123', $tunde->password));
        Notification::assertSentTo($tunde, AddedToWorkspace::class, fn ($notification) => $notification->setPasswordUrl === null);
    }

    public function test_an_owner_can_resend_an_invitation(): void
    {
        $member = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->owner)->post("/t/acme/team/{$member->id}/invite")
            ->assertSessionHas('status', "A new invitation has been emailed to {$member->email}.");

        Notification::assertSentTo($member, AddedToWorkspace::class, fn ($notification) => $notification->setPasswordUrl !== null);
    }

    public function test_importing_a_csv_with_a_header_adds_and_invites_people(): void
    {
        $file = $this->csv("\xEF\xBB\xBFname,email,role\nAda Obi,ADA@acme.test,member\nTunde Bello,tunde@acme.test,Owner\n,chi.eze@acme.test,\n");

        $this->actingAs($this->owner)->post('/t/acme/team/import', ['file' => $file])
            ->assertRedirect('/t/acme/team/import')
            ->assertSessionHas('import', fn ($result) => $result['created'] === 3 && $result['skipped'] === []);

        $this->assertSame('Ada Obi', $this->user('ada@acme.test')->name);
        $this->assertSame(UserRole::Owner, $this->user('tunde@acme.test')->role);
        $this->assertSame('Chi Eze', $this->user('chi.eze@acme.test')->name, 'A missing name is derived from the email.');
        Notification::assertSentTo($this->user('ada@acme.test'), AddedToWorkspace::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'team.imported', 'description' => 'Imported 3 people from a CSV file']);
    }

    public function test_import_skips_bad_duplicate_and_existing_rows_with_reasons(): void
    {
        $file = $this->csv("Ada Obi,ada@acme.test\nNot An Email,nope\nAda Again,ada@acme.test\nOlu,olu@acme.test\nBad Role,bad@acme.test,admin\n");

        $this->actingAs($this->owner)->post('/t/acme/team/import', ['file' => $file])
            ->assertSessionHas('import', function ($result) {
                $reasons = collect($result['skipped'])->pluck('reason', 'line')->all();

                return $result['created'] === 1
                    && $reasons === [
                        2 => 'not a valid email address',
                        3 => 'listed twice in the file',
                        4 => 'already in the workspace',
                        5 => 'unknown role "admin" (use member or owner)',
                    ];
            });
    }

    public function test_import_can_assign_a_course_to_new_and_existing_people(): void
    {
        $course = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Data Security', 'slug' => 'data-security', 'is_published' => true]);
        $existing = User::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'existing@acme.test']);

        $this->actingAs($this->owner)->post('/t/acme/team/import', [
            'file' => $this->csv("name,email\nNew Person,new@acme.test\nExisting,existing@acme.test\n"),
            'course_id' => $course->id,
            'due_at' => now()->addWeeks(2)->toDateString(),
        ])->assertSessionHas('import', fn ($result) => $result['course'] === 'Data Security' && $result['assignedExisting'] === 1);

        foreach ([$this->user('new@acme.test'), $existing] as $person) {
            $this->assertDatabaseHas('course_assignments', ['course_id' => $course->id, 'user_id' => $person->id]);
            Notification::assertSentTo($person, CourseAssigned::class);
        }
    }

    public function test_the_template_can_be_downloaded_and_members_cannot_import(): void
    {
        $response = $this->actingAs($this->owner)->get('/t/acme/team/import/template.csv');
        $this->assertStringStartsWith("name,email,role\n", $response->streamedContent());

        $member = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Member]);
        $this->actingAs($member)->post('/t/acme/team/import', ['file' => $this->csv("a@b.test\n")])->assertForbidden();
    }
}
