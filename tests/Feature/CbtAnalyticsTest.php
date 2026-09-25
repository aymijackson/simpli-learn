<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CbtAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function examWithOneQuestion(Tenant $tenant): Exam
    {
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Quiz',
            'slug' => 'quiz',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
        ]);

        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        return $exam;
    }

    /** Creates a submitted attempt directly (bypassing the timed flow) with a given score and duration. */
    private function submittedAttempt(Tenant $tenant, Exam $exam, User $user, int $score, int $minutesTaken): void
    {
        $exam->attempts()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes($minutesTaken),
            'submitted_at' => now(),
            'score' => $score,
        ]);
    }

    public function test_a_member_cannot_access_analytics(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);
        $exam = $this->examWithOneQuestion($tenant);

        $this->actingAs($member)->get('/t/acme/cbt/manage/analytics')->assertForbidden();
        $this->actingAs($member)->get("/t/acme/cbt/manage/analytics/{$exam->slug}")->assertForbidden();
    }

    public function test_the_overview_reports_correct_aggregate_numbers(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->examWithOneQuestion($tenant);
        $learnerA = User::factory()->create(['tenant_id' => $tenant->id]);
        $learnerB = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->submittedAttempt($tenant, $exam, $learnerA, 40, 10); // fail
        $this->submittedAttempt($tenant, $exam, $learnerB, 80, 20); // pass

        $html = $this->actingAs($owner)->get('/t/acme/cbt/manage/analytics')->assertOk()->getContent();

        // 2 attempts, 2 learners, 50% pass rate, scores 40/60/80, time 10/15/20.
        $this->assertStringContainsString('Quiz', $html);
        $this->assertStringContainsString('50%', $html);
        $this->assertStringContainsString('40% / 60% / 80%', $html);
        $this->assertStringContainsString('10 / 15 / 20', $html);
    }

    public function test_score_range_filter_narrows_the_shortlist_and_recomputed_stats(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->examWithOneQuestion($tenant);
        $learnerA = User::factory()->create(['tenant_id' => $tenant->id]);
        $learnerB = User::factory()->create(['tenant_id' => $tenant->id]);
        $learnerC = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->submittedAttempt($tenant, $exam, $learnerA, 20, 5);
        $this->submittedAttempt($tenant, $exam, $learnerB, 60, 5);
        $this->submittedAttempt($tenant, $exam, $learnerC, 90, 5);

        $response = $this->actingAs($owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}?min_score=50");

        $response->assertOk();
        $response->assertSee($learnerB->name);
        $response->assertSee($learnerC->name);
        $response->assertDontSee($learnerA->name);
    }

    public function test_status_filter_narrows_to_passed_or_failed_only(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->examWithOneQuestion($tenant); // pass_percentage = 50
        $learnerPass = User::factory()->create(['tenant_id' => $tenant->id]);
        $learnerFail = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->submittedAttempt($tenant, $exam, $learnerPass, 100, 5);
        $this->submittedAttempt($tenant, $exam, $learnerFail, 0, 5);

        $passedOnly = $this->actingAs($owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}?status=passed");
        $passedOnly->assertSee($learnerPass->name);
        $passedOnly->assertDontSee($learnerFail->name);

        $failedOnly = $this->actingAs($owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}?status=failed");
        $failedOnly->assertSee($learnerFail->name);
        $failedOnly->assertDontSee($learnerPass->name);
    }

    public function test_date_range_filter_narrows_by_submission_date(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->examWithOneQuestion($tenant);
        $learnerOld = User::factory()->create(['tenant_id' => $tenant->id]);
        $learnerRecent = User::factory()->create(['tenant_id' => $tenant->id]);

        $exam->attempts()->create([
            'tenant_id' => $tenant->id, 'user_id' => $learnerOld->id,
            'started_at' => now()->subDays(10), 'submitted_at' => now()->subDays(10), 'score' => 70,
        ]);
        $exam->attempts()->create([
            'tenant_id' => $tenant->id, 'user_id' => $learnerRecent->id,
            'started_at' => now(), 'submitted_at' => now(), 'score' => 70,
        ]);

        $response = $this->actingAs($owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}?from=".now()->subDay()->toDateString());

        $response->assertSee($learnerRecent->name);
        $response->assertDontSee($learnerOld->name);
    }

    public function test_csv_export_respects_the_active_filter(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->examWithOneQuestion($tenant);
        $learnerA = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Alice']);
        $learnerB = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Bob']);

        $this->submittedAttempt($tenant, $exam, $learnerA, 30, 5);
        $this->submittedAttempt($tenant, $exam, $learnerB, 90, 5);

        $response = $this->actingAs($owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}/export.csv?min_score=50");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('Bob', $content);
        $this->assertStringNotContainsString('Alice', $content);
    }

    public function test_pdf_export_returns_a_pdf(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->examWithOneQuestion($tenant);
        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->submittedAttempt($tenant, $exam, $learner, 75, 5);

        $response = $this->actingAs($owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}/export.pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_another_tenants_attempts_never_appear_in_this_tenants_analytics(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner('acme');
        [$tenantB] = $this->tenantWithOwner('other');
        $examA = $this->examWithOneQuestion($tenantA);
        $examB = Exam::create([
            'tenant_id' => $tenantB->id, 'title' => 'Other Quiz', 'slug' => 'quiz',
            'duration_minutes' => 30, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $learnerB = User::factory()->create(['tenant_id' => $tenantB->id]);
        $this->submittedAttempt($tenantB, $examB, $learnerB, 100, 5);

        $html = $this->actingAs($ownerA)->get('/t/acme/cbt/manage/analytics')->assertOk()->getContent();

        $this->assertStringNotContainsString('Other Quiz', $html);
    }
}
