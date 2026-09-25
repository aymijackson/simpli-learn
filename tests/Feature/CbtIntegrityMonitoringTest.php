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

class CbtIntegrityMonitoringTest extends TestCase
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

    public function test_an_event_is_logged_when_monitoring_is_enabled(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['integrity_monitoring_enabled' => true]);
        $attempt = $this->startAttempt($user, $exam);

        $response = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", [
            'event_type' => 'window_blur',
        ]);

        $response->assertNoContent();
        $this->assertSame(1, $attempt->fresh()->integrityEvents()->count());
        $this->assertSame('window_blur', $attempt->fresh()->integrityEvents()->first()->event_type->value);
    }

    public function test_events_are_rejected_when_monitoring_is_disabled(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['integrity_monitoring_enabled' => false]);
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", [
            'event_type' => 'window_blur',
        ])->assertNotFound();

        $this->assertSame(0, $attempt->fresh()->integrityEvents()->count());
    }

    public function test_events_are_rejected_for_another_users_attempt(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $owner = $tenant->users()->first();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $intruder = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['integrity_monitoring_enabled' => true]);
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($intruder)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", [
            'event_type' => 'window_blur',
        ])->assertForbidden();
    }

    public function test_events_are_rejected_once_the_attempt_is_submitted(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['integrity_monitoring_enabled' => true]);
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => []]);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", [
            'event_type' => 'copy',
        ])->assertNotFound();
    }

    public function test_monitoring_works_the_same_under_one_at_a_time_navigation(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, [
            'integrity_monitoring_enabled' => true,
            'navigation_mode' => 'one_at_a_time',
        ]);
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", [
            'event_type' => 'paste',
        ])->assertNoContent();

        $this->assertSame(1, $attempt->fresh()->integrityEvents()->count());
    }

    public function test_owner_sees_the_flag_count_and_detail_log(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['integrity_monitoring_enabled' => true]);
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", ['event_type' => 'window_blur']);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", ['event_type' => 'copy']);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => []]);

        $html = $this->actingAs($owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}")->assertOk()->getContent();
        $this->assertStringContainsString('2 flags', $html);

        $this->actingAs($owner)
            ->get("/t/acme/cbt/manage/analytics/{$exam->slug}/attempts/{$attempt->id}/integrity")
            ->assertOk()
            ->assertSee('Exam window lost focus', escape: false)
            ->assertSee('Copied text from the exam', escape: false);
    }

    public function test_another_tenants_owner_cannot_see_the_integrity_log(): void
    {
        [$tenantA] = $this->tenantWithOwner('acme');
        [$tenantB, $ownerB] = $this->tenantWithOwner('other');
        $user = User::factory()->create(['tenant_id' => $tenantA->id]);
        $exam = $this->examWithOneQuestion($tenantA, ['integrity_monitoring_enabled' => true]);
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", ['event_type' => 'window_blur']);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => []]);

        $this->actingAs($ownerB)
            ->get("/t/other/cbt/manage/analytics/{$exam->slug}/attempts/{$attempt->id}/integrity")
            ->assertNotFound();
    }
}
