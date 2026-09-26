<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\CourseAssigned;
use App\Notifications\CourseAssignmentReminder;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseAssignment;
use Elibrary\Lms\Models\Enrollment;
use Elibrary\Lms\Models\Lesson;
use Elibrary\Lms\Models\LessonProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CourseAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private Course $course;

    /** @var list<Lesson> */
    private array $lessons = [];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner, 'name' => 'Olu Owner']);
        $this->course = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Data Security', 'slug' => 'data-security', 'is_published' => true]);
        foreach ([1, 2] as $position) {
            $this->lessons[] = $this->course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => "Lesson {$position}", 'content' => '<p>x</p>', 'position' => $position]);
        }
    }

    private function member(string $name): User
    {
        return User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Member, 'name' => $name]);
    }

    private function complete(User $user, int $lessons): void
    {
        Enrollment::withoutGlobalScopes()->firstOrCreate(['tenant_id' => $this->tenant->id, 'course_id' => $this->course->id, 'user_id' => $user->id]);
        foreach (array_slice($this->lessons, 0, $lessons) as $lesson) {
            LessonProgress::create(['tenant_id' => $this->tenant->id, 'lesson_id' => $lesson->id, 'user_id' => $user->id, 'completed_at' => now()]);
        }
    }

    public function test_the_report_shows_each_persons_status(): void
    {
        $this->complete($done = $this->member('Ada Done'), 2);
        $this->complete($this->member('Bola Halfway'), 1);
        $this->complete($this->member('Chi Enrolled'), 0);
        $this->member('Dayo Nothing');

        $response = $this->actingAs($this->owner)->get('/t/acme/lms/manage/courses/data-security/report');

        $response->assertOk()
            ->assertSee('Ada Done')->assertSee('Completed')
            ->assertSee('Bola Halfway')->assertSee('In progress')
            ->assertSee('Chi Enrolled')->assertSee('Not started')
            ->assertSee('Dayo Nothing')->assertSee('Not enrolled');

        // 1 of 5 people (including the owner) has completed it.
        $response->assertSee('20%');
    }

    public function test_the_report_can_be_exported_as_csv(): void
    {
        $this->complete($this->member('Ada Done'), 2);

        $response = $this->actingAs($this->owner)->get('/t/acme/lms/manage/courses/data-security/report.csv');

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Course,Name,Email,Role,Status', $csv);
        $this->assertMatchesRegularExpression('/"?Data Security"?,"?Ada Done"?,[^,]+,Member,Completed,100,/', $csv);
    }

    public function test_members_cannot_see_the_report(): void
    {
        $this->actingAs($this->member('Nosy Member'))->get('/t/acme/lms/manage/courses/data-security/report')->assertForbidden();
    }

    public function test_assigning_to_everyone_enrolls_and_emails_them_with_the_due_date(): void
    {
        $ada = $this->member('Ada');
        $bola = $this->member('Bola');

        $this->actingAs($this->owner)->post('/t/acme/lms/manage/courses/data-security/assignments', [
            'who' => 'everyone',
            'due_at' => now()->addDays(14)->toDateString(),
        ])->assertRedirect('/t/acme/lms/manage/courses/data-security/report');

        foreach ([$ada, $bola, $this->owner] as $person) {
            $this->assertDatabaseHas('course_assignments', ['course_id' => $this->course->id, 'user_id' => $person->id, 'assigned_by_user_id' => $this->owner->id]);
            $this->assertDatabaseHas('enrollments', ['course_id' => $this->course->id, 'user_id' => $person->id]);
        }

        Notification::assertSentTo($ada, CourseAssigned::class, function ($notification) use ($ada) {
            $text = implode(' ', $notification->toMail($ada)->introLines);

            return str_contains($text, 'Olu Owner has assigned you the course **Data Security**')
                && str_contains($text, now()->addDays(14)->format('j F Y'));
        });
        $this->assertDatabaseHas('activity_logs', ['action' => 'courses.assigned']);
    }

    public function test_assigning_to_selected_people_only_assigns_them(): void
    {
        $ada = $this->member('Ada');
        $bola = $this->member('Bola');

        $this->actingAs($this->owner)->post('/t/acme/lms/manage/courses/data-security/assignments', [
            'who' => 'selected',
            'user_ids' => [$ada->id],
        ]);

        $this->assertDatabaseHas('course_assignments', ['user_id' => $ada->id, 'due_at' => null]);
        $this->assertDatabaseMissing('course_assignments', ['user_id' => $bola->id]);
        Notification::assertNotSentTo($bola, CourseAssigned::class);
    }

    public function test_assigning_ignores_people_from_other_workspaces(): void
    {
        $other = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        $outsider = User::factory()->create(['tenant_id' => $other->id]);

        $this->actingAs($this->owner)->post('/t/acme/lms/manage/courses/data-security/assignments', [
            'who' => 'selected',
            'user_ids' => [$outsider->id],
        ]);

        $this->assertDatabaseMissing('course_assignments', ['user_id' => $outsider->id]);
    }

    public function test_assigned_courses_appear_on_the_learners_dashboard_until_completed(): void
    {
        $ada = $this->member('Ada');
        $this->actingAs($this->owner)->post('/t/acme/lms/manage/courses/data-security/assignments', [
            'who' => 'selected', 'user_ids' => [$ada->id], 'due_at' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($ada)->get('/t/acme')->assertSee('Assigned to you')->assertSee('Data Security');

        $this->complete($ada, 2);
        $this->actingAs($ada)->get('/t/acme')->assertDontSee('Assigned to you');
    }

    public function test_reminders_go_to_people_due_soon_or_overdue_but_not_to_finishers(): void
    {
        $soon = $this->member('Due Soon');
        $later = $this->member('Due Later');
        $overdue = $this->member('Overdue');
        $finished = $this->member('Finished');
        $this->complete($finished, 2);

        foreach ([[$soon, 2], [$later, 20], [$overdue, -5], [$finished, 1]] as [$person, $days]) {
            CourseAssignment::create(['tenant_id' => $this->tenant->id, 'course_id' => $this->course->id, 'user_id' => $person->id, 'due_at' => now()->addDays($days)]);
            Enrollment::withoutGlobalScopes()->firstOrCreate(['tenant_id' => $this->tenant->id, 'course_id' => $this->course->id, 'user_id' => $person->id]);
        }
        app(Tenancy::class)->forget();

        $this->artisan('courses:send-reminders')->expectsOutputToContain('Sent 2 reminders.')->assertExitCode(0);

        Notification::assertSentTo($soon, CourseAssignmentReminder::class);
        Notification::assertSentTo($overdue, CourseAssignmentReminder::class, fn ($n) => str_starts_with($n->toMail($overdue)->subject, 'Overdue:'));
        Notification::assertNotSentTo($later, CourseAssignmentReminder::class);
        Notification::assertNotSentTo($finished, CourseAssignmentReminder::class);
    }

    public function test_reminders_are_not_repeated_every_day(): void
    {
        $soon = $this->member('Due Soon');
        CourseAssignment::create(['tenant_id' => $this->tenant->id, 'course_id' => $this->course->id, 'user_id' => $soon->id, 'due_at' => now()->addDays(2)]);
        app(Tenancy::class)->forget();

        $this->artisan('courses:send-reminders')->expectsOutputToContain('Sent 1 reminder.');
        $this->artisan('courses:send-reminders')->expectsOutputToContain('Sent 0 reminders.');

        // Once overdue, it's reminded again (at most weekly).
        $this->travel(4)->days();
        $this->artisan('courses:send-reminders')->expectsOutputToContain('Sent 1 reminder.');
        $this->travel(3)->days();
        $this->artisan('courses:send-reminders')->expectsOutputToContain('Sent 0 reminders.');
    }
}
