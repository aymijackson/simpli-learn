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

class CbtWeightedScoringTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithCbt(string $slug = 'acme'): Tenant
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);

        return $tenant;
    }

    private function examWithTwoQuestions(Tenant $tenant, int $pointsA = 1, int $pointsB = 1): array
    {
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Weighted Quiz',
            'slug' => 'weighted-quiz',
            'duration_minutes' => 15,
            'pass_percentage' => 50,
            'is_published' => true,
        ]);

        $questionA = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'A', 'position' => 0, 'points' => $pointsA]);
        $correctA = $questionA->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Correct', 'is_correct' => true, 'position' => 0]);
        $questionA->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        $questionB = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'B', 'position' => 1, 'points' => $pointsB]);
        $questionB->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Correct', 'is_correct' => true, 'position' => 0]);
        $wrongB = $questionB->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        return [$exam, $questionA, $correctA, $questionB, $wrongB];
    }

    private function startAttempt(User $user, Exam $exam): ExamAttempt
    {
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        return $exam->fresh()->attempts()->where('user_id', $user->id)->first();
    }

    public function test_default_points_of_one_each_reproduces_the_old_unweighted_average(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$exam, $questionA, $correctA, $questionB, $wrongB] = $this->examWithTwoQuestions($tenant);
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$questionA->id => $correctA->id, $questionB->id => $wrongB->id],
        ]);

        $this->assertSame(50, $attempt->fresh()->score);
    }

    public function test_a_higher_weighted_question_counts_for_more_of_the_final_score(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$exam, $questionA, $correctA, $questionB, $wrongB] = $this->examWithTwoQuestions($tenant, pointsA: 1, pointsB: 3);
        $attempt = $this->startAttempt($user, $exam);

        // Correct on the 3-point question, wrong on the 1-point one: 3 / 4 total points.
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$questionB->id => $questionB->options->firstWhere('is_correct', true)->id],
        ]);

        $this->assertSame(75, $attempt->fresh()->score);
    }

    public function test_owner_can_set_points_via_the_question_form_and_validation_rejects_zero(): void
    {
        $tenant = $this->tenantWithCbt();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);
        $exam = Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 30, 'pass_percentage' => 50, 'is_published' => true,
        ]);

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions", [
            'question_text' => 'Worth 5', 'position' => 0, 'points' => 5,
        ])->assertRedirect();

        $this->assertSame(5, $exam->fresh()->questions()->first()->points);

        $response = $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions", [
            'question_text' => 'Invalid', 'position' => 1, 'points' => 0,
        ]);

        $response->assertSessionHasErrors('points');
    }

    public function test_omitting_points_defaults_to_one(): void
    {
        $tenant = $this->tenantWithCbt();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);
        $exam = Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 30, 'pass_percentage' => 50, 'is_published' => true,
        ]);

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions", [
            'question_text' => 'Default weight', 'position' => 0,
        ])->assertRedirect();

        $this->assertSame(1, $exam->fresh()->questions()->first()->points);
    }
}
