<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CbtTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithCbt(string $slug = 'acme'): Tenant
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);

        return $tenant;
    }

    private function examWithTwoQuestions(Tenant $tenant): Exam
    {
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Science Quiz',
            'slug' => 'science-quiz',
            'duration_minutes' => 15,
            'pass_percentage' => 50,
            'is_published' => true,
        ]);

        foreach (range(1, 2) as $i) {
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

    private function examWithOneMultiSelectQuestion(Tenant $tenant, string $scoringMethod): array
    {
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Multi-Select Quiz',
            'slug' => 'multi-select-quiz',
            'duration_minutes' => 15,
            'pass_percentage' => 50,
            'is_published' => true,
        ]);

        $question = $exam->questions()->create([
            'tenant_id' => $tenant->id,
            'question_text' => 'Pick the correct options',
            'scoring_method' => $scoringMethod,
            'position' => 0,
        ]);

        $optionA = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);
        $optionB = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'B', 'is_correct' => true, 'position' => 1]);
        $optionC = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'C', 'is_correct' => false, 'position' => 2]);

        return [$exam, $question, $optionA, $optionB, $optionC];
    }

    private function startAttempt(User $user, Exam $exam): \Elibrary\Cbt\Models\ExamAttempt
    {
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        return $exam->fresh()->attempts()->where('user_id', $user->id)->first();
    }

    public function test_all_or_nothing_scoring_gives_full_credit_only_for_an_exact_match(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$exam, $question, $optionA, $optionB, $optionC] = $this->examWithOneMultiSelectQuestion($tenant, 'all_or_nothing');
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => [$optionA->id, $optionB->id]],
        ]);

        $this->assertSame(100, $attempt->fresh()->score);
    }

    public function test_all_or_nothing_scoring_gives_zero_for_a_partial_match(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$exam, $question, $optionA, $optionB, $optionC] = $this->examWithOneMultiSelectQuestion($tenant, 'all_or_nothing');
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => [$optionA->id]],
        ]);

        $this->assertSame(0, $attempt->fresh()->score);
    }

    public function test_partial_credit_scoring_gives_proportional_credit(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$exam, $question, $optionA, $optionB, $optionC] = $this->examWithOneMultiSelectQuestion($tenant, 'partial_credit');
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => [$optionA->id]],
        ]);

        // (1 correct - 0 incorrect) / 2 total correct = 0.5 -> 50%
        $this->assertSame(50, $attempt->fresh()->score);
    }

    public function test_partial_credit_scoring_floors_at_zero_when_incorrect_picks_outweigh_correct_ones(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$exam, $question, $optionA, $optionB, $optionC] = $this->examWithOneMultiSelectQuestion($tenant, 'partial_credit');
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => [$optionA->id, $optionC->id]],
        ]);

        // (1 correct - 1 incorrect) / 2 total correct = 0 -> floored, not negative
        $this->assertSame(0, $attempt->fresh()->score);
    }

    public function test_selecting_extra_option_ids_from_another_question_is_ignored(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$exam, $question, $optionA] = $this->examWithOneMultiSelectQuestion($tenant, 'all_or_nothing');
        $foreignOption = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Other', 'position' => 1])
            ->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Foreign', 'is_correct' => true, 'position' => 0]);
        $attempt = $this->startAttempt($user, $exam);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => [$optionA->id, $foreignOption->id, 999999]],
        ]);

        $answer = $attempt->fresh()->answers()->where('question_id', $question->id)->first();
        $this->assertSame([$optionA->id], $answer->selectedOptions->pluck('id')->all());
    }

    public function test_submitting_all_correct_answers_scores_100_and_passes(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->first();

        $answers = $exam->questions->mapWithKeys(fn ($q) => [
            $q->id => $q->options->firstWhere('is_correct', true)->id,
        ])->all();

        $response = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => $answers,
        ]);

        $attempt->refresh();

        $this->assertSame(100, $attempt->score);
        $this->assertTrue($attempt->passed());
        $response->assertRedirect();
    }

    public function test_submitting_all_wrong_answers_scores_0_and_fails(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->first();

        $answers = $exam->questions->mapWithKeys(fn ($q) => [
            $q->id => $q->options->firstWhere('is_correct', false)->id,
        ])->all();

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => $answers,
        ]);

        $attempt->refresh();

        $this->assertSame(0, $attempt->score);
        $this->assertFalse($attempt->passed());
    }

    public function test_a_user_cannot_view_another_users_attempt(): void
    {
        $tenant = $this->tenantWithCbt();
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $intruder = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);

        $this->actingAs($owner)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->first();

        $this->actingAs($intruder)
            ->get("/t/acme/cbt/attempts/{$attempt->id}")
            ->assertForbidden();
    }

    public function test_a_submitted_attempt_cannot_be_retaken(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->first();

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => []]);

        $this->actingAs($user)
            ->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => []])
            ->assertForbidden();
    }

    public function test_submitting_after_the_deadline_forfeits_the_attempt_when_time_limit_is_enforced(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);
        $this->assertTrue($exam->fresh()->enforce_time_limit);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->first();
        $attempt->update(['started_at' => now()->subMinutes($exam->duration_minutes + 5)]);

        $answers = $exam->questions->mapWithKeys(fn ($q) => [
            $q->id => $q->options->firstWhere('is_correct', true)->id,
        ])->all();

        $response = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => $answers,
        ]);

        $attempt->refresh();

        $response->assertRedirect(route('cbt.attempts.result', $attempt));
        $this->assertTrue($attempt->isSubmitted());
        $this->assertSame(0, $attempt->score);
        $this->assertCount(0, $attempt->answers, 'no answers should be recorded for a forfeited attempt');
    }

    public function test_opening_an_expired_attempt_auto_forfeits_it(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->first();
        $attempt->update(['started_at' => now()->subMinutes($exam->duration_minutes + 5)]);

        $response = $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}");

        $attempt->refresh();

        $response->assertRedirect(route('cbt.attempts.result', $attempt));
        $this->assertTrue($attempt->isSubmitted());
        $this->assertSame(0, $attempt->score);
    }

    public function test_late_submission_is_still_graded_normally_when_time_limit_enforcement_is_disabled(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);
        $exam->update(['enforce_time_limit' => false]);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->first();
        $attempt->update(['started_at' => now()->subMinutes($exam->duration_minutes + 5)]);

        $answers = $exam->questions->mapWithKeys(fn ($q) => [
            $q->id => $q->options->firstWhere('is_correct', true)->id,
        ])->all();

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => $answers,
        ]);

        $attempt->refresh();

        $this->assertSame(100, $attempt->score);
    }

    public function test_starting_an_exam_twice_while_an_attempt_is_in_progress_reuses_the_same_attempt(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $first = $exam->fresh()->attempts()->where('user_id', $user->id)->first();

        // Starting again before finishing must not grant a fresh timer.
        $response = $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        $this->assertSame(1, $exam->fresh()->attempts()->where('user_id', $user->id)->count());
        $response->assertRedirect(route('cbt.attempts.take', $first));
    }

    public function test_starting_after_the_previous_attempt_expired_forfeits_it_and_starts_a_fresh_one(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $first = $exam->fresh()->attempts()->where('user_id', $user->id)->first();
        $first->update(['started_at' => now()->subMinutes($exam->duration_minutes + 5)]);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        $this->assertTrue($first->fresh()->isSubmitted());
        $this->assertSame(0, $first->fresh()->score);

        $attempts = $exam->fresh()->attempts()->where('user_id', $user->id)->get();
        $this->assertCount(2, $attempts);
        $second = $attempts->firstWhere('id', '!=', $first->id);
        $this->assertFalse($second->isSubmitted());
    }

    public function test_a_second_attempt_can_be_started_after_the_first_is_submitted(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $first = $exam->fresh()->attempts()->where('user_id', $user->id)->first();
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$first->id}", ['answers' => []]);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        $this->assertSame(2, $exam->fresh()->attempts()->where('user_id', $user->id)->count());
    }

    public function test_a_second_submit_request_for_the_same_attempt_is_rejected(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);
        $attempt = $this->startAttempt($user, $exam);

        $wrongAnswers = $exam->questions->mapWithKeys(fn ($q) => [
            $q->id => [$q->options->firstWhere('is_correct', false)->id],
        ])->all();
        $correctAnswers = $exam->questions->mapWithKeys(fn ($q) => [
            $q->id => [$q->options->firstWhere('is_correct', true)->id],
        ])->all();

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => $wrongAnswers]);
        $this->assertSame(0, $attempt->fresh()->score);

        // A retried/duplicate submit must not be allowed to re-grade the
        // already-submitted attempt with a different (better) answer set.
        $this->actingAs($user)
            ->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => $correctAnswers])
            ->assertForbidden();
        $this->assertSame(0, $attempt->fresh()->score);
    }

    public function test_the_all_at_once_take_page_renders_instructions_sections_and_correct_input_types(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Mixed Quiz',
            'slug' => 'mixed-quiz',
            'instructions' => '<p>Read carefully.</p>',
            'duration_minutes' => 15,
            'pass_percentage' => 50,
            'is_published' => true,
        ]);
        $section = $exam->sections()->create(['tenant_id' => $tenant->id, 'title' => 'Warm-up', 'instructions' => 'Take it easy.', 'position' => 0]);

        $single = $exam->questions()->create([
            'tenant_id' => $tenant->id, 'question_text' => 'Single Q', 'answer_type' => 'single',
            'exam_section_id' => $section->id, 'position' => 0,
        ]);
        $single->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);
        $single->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'B', 'is_correct' => false, 'position' => 1]);

        $multiple = $exam->questions()->create([
            'tenant_id' => $tenant->id, 'question_text' => 'Multi Q', 'answer_type' => 'multiple', 'position' => 1,
        ]);
        $multiple->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'C', 'is_correct' => true, 'position' => 0]);
        $multiple->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'D', 'is_correct' => true, 'position' => 1]);

        $attempt = $this->startAttempt($user, $exam);

        $html = $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}")->assertOk()->getContent();

        $this->assertStringContainsString('Read carefully.', $html);
        $this->assertStringContainsString('Warm-up', $html);
        $this->assertStringContainsString('Take it easy.', $html);
        $this->assertStringContainsString("name=\"answers[{$single->id}]\"", $html);
        $this->assertStringContainsString("type=\"radio\"", $html);
        $this->assertStringContainsString("name=\"answers[{$multiple->id}][]\"", $html);
        $this->assertStringContainsString("name=\"flagged[]\" value=\"{$single->id}\"", $html);
    }

    public function test_flagging_a_question_on_submit_is_recorded(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithTwoQuestions($tenant);
        $attempt = $this->startAttempt($user, $exam);
        $firstQuestion = $exam->questions->first();

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [],
            'flagged' => [(string) $firstQuestion->id],
        ]);

        $answer = $attempt->fresh()->answers()->where('question_id', $firstQuestion->id)->first();
        $this->assertTrue($answer->is_flagged);

        $otherAnswer = $attempt->fresh()->answers()->where('question_id', '!=', $firstQuestion->id)->first();
        $this->assertFalse($otherAnswer->is_flagged);
    }
}
