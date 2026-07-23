<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CbtOneAtATimeTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithCbt(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);

        return $tenant;
    }

    private function examWithThreeQuestions(Tenant $tenant, bool $allowBackwardNavigation): Exam
    {
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Paged Quiz',
            'slug' => 'paged-quiz',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
            'navigation_mode' => 'one_at_a_time',
            'allow_backward_navigation' => $allowBackwardNavigation,
        ]);

        foreach (range(1, 3) as $i) {
            $question = $exam->questions()->create([
                'tenant_id' => $tenant->id,
                'question_text' => "Question {$i}",
                'position' => $i,
            ]);

            $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Correct', 'is_correct' => true, 'position' => 0]);
            $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);
        }

        return $exam;
    }

    private function startAttempt(User $user, Exam $exam): ExamAttempt
    {
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        return $exam->fresh()->attempts()->where('user_id', $user->id)->first();
    }

    public function test_starting_a_one_at_a_time_exam_lands_on_question_one(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, true);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();

        $this->actingAs($user)
            ->get("/t/acme/cbt/attempts/{$attempt->id}")
            ->assertRedirect(route('cbt.attempts.questions.show', [$attempt, 1]));
    }

    public function test_answering_a_question_saves_it_and_advances_to_the_next(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, true);
        $attempt = $this->startAttempt($user, $exam);
        $q1 = $exam->questions->first();
        $correctOption = $q1->options->firstWhere('is_correct', true);

        $response = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/1", [
            'answers' => $correctOption->id,
        ]);

        $response->assertRedirect(route('cbt.attempts.questions.show', [$attempt, 2]));
        $answer = $attempt->fresh()->answers()->where('question_id', $q1->id)->first();
        $this->assertNotNull($answer);
        $this->assertSame([$correctOption->id], $answer->selectedOptions->pluck('id')->all());
    }

    public function test_answering_the_last_question_redirects_to_the_review_page(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, true);
        $attempt = $this->startAttempt($user, $exam);

        foreach ($exam->questions as $index => $question) {
            $response = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/".($index + 1), [
                'answers' => $question->options->firstWhere('is_correct', true)->id,
            ]);
        }

        $response->assertRedirect(route('cbt.attempts.review', $attempt));
    }

    public function test_the_review_page_shows_answered_unanswered_and_flagged_status(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, true);
        $attempt = $this->startAttempt($user, $exam);
        [$q1, $q2, $q3] = $exam->questions->all();

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/1", [
            'answers' => $q1->options->firstWhere('is_correct', true)->id,
        ]);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/2", [
            'flagged' => '1',
        ]);

        $html = $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}/questions/3")->assertOk();

        $reviewHtml = $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}/review")->assertOk()->getContent();

        $this->assertStringContainsString('Answered', $reviewHtml);
        $this->assertStringContainsString('Unanswered', $reviewHtml);
        $this->assertStringContainsString('Flagged', $reviewHtml);
    }

    public function test_submitting_from_the_review_page_grades_the_incrementally_saved_answers(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, true);
        $attempt = $this->startAttempt($user, $exam);

        foreach ($exam->questions as $index => $question) {
            $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/".($index + 1), [
                'answers' => $question->options->firstWhere('is_correct', true)->id,
            ]);
        }

        $response = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}");

        $response->assertRedirect(route('cbt.attempts.result', $attempt));
        $this->assertSame(100, $attempt->fresh()->score);
        $this->assertTrue($attempt->fresh()->isSubmitted());
    }

    public function test_backward_navigation_disabled_redirects_away_from_an_earlier_page(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, false);
        $attempt = $this->startAttempt($user, $exam);
        $q1 = $exam->questions->first();

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/1", [
            'answers' => $q1->options->firstWhere('is_correct', true)->id,
        ]);

        // Now on page 2; trying to revisit page 1 must bounce back to page 2.
        $this->actingAs($user)
            ->get("/t/acme/cbt/attempts/{$attempt->id}/questions/1")
            ->assertRedirect(route('cbt.attempts.questions.show', [$attempt, 2]));
    }

    public function test_backward_navigation_disabled_also_blocks_skipping_ahead(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, false);
        $attempt = $this->startAttempt($user, $exam);

        // Still on page 1 (nothing answered yet) — jumping straight to page 3 must bounce back to page 1.
        $this->actingAs($user)
            ->get("/t/acme/cbt/attempts/{$attempt->id}/questions/3")
            ->assertRedirect(route('cbt.attempts.questions.show', [$attempt, 1]));
    }

    public function test_backward_navigation_allowed_lets_a_learner_revisit_and_change_an_earlier_answer(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, true);
        $attempt = $this->startAttempt($user, $exam);
        $q1 = $exam->questions->first();
        $wrong = $q1->options->firstWhere('is_correct', false);
        $correct = $q1->options->firstWhere('is_correct', true);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/1", ['answers' => $wrong->id]);

        // Revisit page 1 and change the answer.
        $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}/questions/1")->assertOk();
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/1", ['answers' => $correct->id]);

        $answer = $attempt->fresh()->answers()->where('question_id', $q1->id)->first();
        $this->assertSame([$correct->id], $answer->selectedOptions->pluck('id')->all());
        // Still exactly one saved answer row for this question, not a duplicate.
        $this->assertSame(1, $attempt->fresh()->answers()->where('question_id', $q1->id)->count());
    }

    public function test_refreshing_the_current_page_shows_the_same_question_without_losing_progress(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, false);
        $attempt = $this->startAttempt($user, $exam);
        $q1 = $exam->questions->first();

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/1", [
            'answers' => $q1->options->firstWhere('is_correct', true)->id,
        ]);

        // A plain refresh of the (now current) page 2 must render it, not bounce or reset.
        $this->actingAs($user)
            ->get("/t/acme/cbt/attempts/{$attempt->id}/questions/2")
            ->assertOk();

        $this->assertSame(1, $attempt->fresh()->answers()->count());
    }

    public function test_a_locked_navigation_exam_still_forfeits_on_expiry(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithThreeQuestions($tenant, false);
        $attempt = $this->startAttempt($user, $exam);
        $attempt->update(['started_at' => now()->subMinutes($exam->duration_minutes + 5)]);

        $this->actingAs($user)
            ->get("/t/acme/cbt/attempts/{$attempt->id}/questions/1")
            ->assertRedirect(route('cbt.attempts.result', $attempt));

        $this->assertTrue($attempt->fresh()->isSubmitted());
        $this->assertSame(0, $attempt->fresh()->score);
    }
}
