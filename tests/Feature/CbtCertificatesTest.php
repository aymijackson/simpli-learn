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

class CbtCertificatesTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function examWithOneQuestion(Tenant $tenant, array $attributes = []): Exam
    {
        $exam = Exam::create(array_merge([
            'tenant_id' => $tenant->id,
            'title' => 'Quiz',
            'slug' => 'quiz',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
        ], $attributes));

        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        return $exam;
    }

    private function startAttempt(User $user, Exam $exam): ExamAttempt
    {
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        return $exam->fresh()->attempts()->where('user_id', $user->id)->first();
    }

    private function submitCorrectly(User $user, Exam $exam, ExamAttempt $attempt): void
    {
        $question = $exam->questions->first();
        $correct = $question->options->firstWhere('is_correct', true);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => $correct->id],
        ]);
    }

    private function submitIncorrectly(User $user, ExamAttempt $attempt): void
    {
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => []]);
    }

    public function test_a_free_policy_auto_issues_a_verified_certificate_on_passing(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'free']);
        $attempt = $this->startAttempt($user, $exam);

        $this->submitCorrectly($user, $exam, $attempt);

        $certificate = $attempt->fresh()->certificate;
        $this->assertNotNull($certificate);
        $this->assertSame('verified', $certificate->tier->value);
    }

    public function test_a_free_policy_issues_nothing_on_failing(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'free']);
        $attempt = $this->startAttempt($user, $exam);

        $this->submitIncorrectly($user, $attempt);

        $this->assertNull($attempt->fresh()->certificate);
    }

    public function test_a_none_policy_issues_nothing_and_shows_no_certificate_ui(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'none']);
        $attempt = $this->startAttempt($user, $exam);

        $this->submitCorrectly($user, $exam, $attempt);

        $this->assertNull($attempt->fresh()->certificate);

        $content = $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}/result")->assertOk()->getContent();
        $this->assertStringNotContainsString('Download certificate', $content);
        $this->assertStringNotContainsString('Get certificate', $content);
    }

    public function test_a_freemium_policy_issues_an_unverified_certificate_immediately(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'freemium']);
        $attempt = $this->startAttempt($user, $exam);

        $this->submitCorrectly($user, $exam, $attempt);

        $certificate = $attempt->fresh()->certificate;
        $this->assertNotNull($certificate);
        $this->assertSame('unverified', $certificate->tier->value);
    }

    public function test_a_paid_policy_issues_nothing_at_submit_time(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid']);
        $attempt = $this->startAttempt($user, $exam);

        $this->submitCorrectly($user, $exam, $attempt);

        $this->assertNull($attempt->fresh()->certificate);
    }

    public function test_inherit_policy_pulls_the_tenants_default(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->actingAs($owner)->put('/t/acme/cbt/manage/certificates/settings', [
            'default_policy' => 'freemium',
            'default_currency' => 'USD',
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'inherit']);
        $attempt = $this->startAttempt($user, $exam);

        $this->submitCorrectly($user, $exam, $attempt);

        $this->assertSame('unverified', $attempt->fresh()->certificate->tier->value);
    }

    public function test_a_learner_can_download_their_own_certificate_but_not_before_passing_or_for_another_user(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $intruder = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'free']);
        $attempt = $this->startAttempt($user, $exam);

        // Before submitting: no certificate yet.
        $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}/certificate")->assertNotFound();

        $this->submitCorrectly($user, $exam, $attempt);

        $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}/certificate")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($intruder)->get("/t/acme/cbt/attempts/{$attempt->id}/certificate")->assertForbidden();
    }

    public function test_the_public_verification_page_resolves_a_valid_token_without_any_tenant_context(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Jane Learner']);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'free']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        $token = $attempt->fresh()->certificate->verification_token;

        // No tenant prefix, no auth at all.
        $response = $this->get("/certificates/verify/{$token}");

        $response->assertOk();
        $response->assertSee('Jane Learner');
        $response->assertSee('Verified certificate', escape: false);
    }

    public function test_the_public_verification_page_404s_for_an_unknown_token(): void
    {
        $this->get('/certificates/verify/not-a-real-token')->assertNotFound();
    }

    public function test_an_unverified_certificate_shows_reduced_detail_on_the_public_page(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'freemium']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        $token = $attempt->fresh()->certificate->verification_token;

        $response = $this->get("/certificates/verify/{$token}");

        $response->assertOk();
        $response->assertSee('Unverified certificate', escape: false);
        $response->assertDontSee('100%');
    }
}
