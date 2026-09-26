<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamPeopleReportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner, 'name' => 'Zed Owner']);
        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id, 'title' => 'Final Assessment', 'slug' => 'final-assessment',
            'duration_minutes' => 30, 'pass_percentage' => 80, 'is_published' => true,
        ]);
    }

    private function attempt(User $user, ?int $score): void
    {
        ExamAttempt::create([
            'tenant_id' => $this->tenant->id, 'exam_id' => $this->exam->id, 'user_id' => $user->id,
            'started_at' => now()->subHour(), 'submitted_at' => $score === null ? null : now()->subMinutes(30), 'score' => $score,
        ]);
    }

    private function member(string $name): User
    {
        return User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => $name]);
    }

    public function test_it_shows_everyone_including_people_who_have_not_attempted(): void
    {
        $this->attempt($passed = $this->member('Ada Passed'), 60);
        $this->attempt($passed, 90);
        $this->attempt($this->member('Bola Failed'), 55);
        $this->attempt($this->member('Chi Sitting'), null);
        $this->member('Dayo Absent');

        $this->actingAs($this->owner)->get('/t/acme/cbt/manage/analytics/final-assessment/people')
            ->assertOk()
            ->assertSeeInOrder(['Ada Passed', 'Passed', '90%', 'Bola Failed', 'Not passed yet', '55%', 'Chi Sitting', 'In progress', 'Dayo Absent', 'Not attempted'])
            ->assertSee('20%'); // 1 of 5 people passed

        $this->actingAs($this->owner)->get('/t/acme/cbt/manage/analytics/final-assessment/people?status=not_attempted')
            ->assertSee('Dayo Absent')->assertDontSee('Ada Passed');
    }

    public function test_it_exports_csv(): void
    {
        $this->attempt($this->member('Ada Passed'), 90);

        $csv = $this->actingAs($this->owner)->get('/t/acme/cbt/manage/analytics/final-assessment/people.csv')->streamedContent();

        $this->assertStringContainsString('Exam,Name,Email,Status,"Best score %"', $csv);
        $this->assertMatchesRegularExpression('/"Final Assessment","Ada Passed",[^,]+,Passed,90,80,1,/', $csv);
    }

    public function test_members_cannot_see_it(): void
    {
        $this->actingAs($this->member('Nosy'))->get('/t/acme/cbt/manage/analytics/final-assessment/people')->assertForbidden();
    }
}
