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

class CbtExamSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithCbt(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);

        return $tenant;
    }

    private function examWithQuestions(Tenant $tenant, int $count, array $attributes = []): Exam
    {
        $exam = Exam::create(array_merge([
            'tenant_id' => $tenant->id,
            'title' => 'Quiz',
            'slug' => 'quiz',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
        ], $attributes));

        foreach (range(1, $count) as $i) {
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

    private function submitAttempt(User $user, $attempt, array $answers): void
    {
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => $answers]);
    }

    public function test_the_new_exam_form_renders_without_an_existing_exam(): void
    {
        $tenant = $this->tenantWithCbt();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)->get('/t/acme/cbt/manage/exams/create')->assertOk();
    }

    public function test_retakes_disabled_blocks_a_second_start_after_the_first_is_submitted(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 2, ['allow_retakes' => false]);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();
        $this->submitAttempt($user, $attempt, []);

        $response = $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        $response->assertRedirect(route('cbt.exams.show', $exam));
        $this->assertSame(1, $exam->fresh()->attempts()->where('user_id', $user->id)->count());

        $html = $this->actingAs($user)->get("/t/acme/cbt/exams/{$exam->slug}")->assertOk()->getContent();
        $this->assertStringContainsString('retakes are not allowed', $html);
    }

    public function test_max_attempts_caps_the_total_number_of_submitted_attempts(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 1, ['allow_retakes' => true, 'max_attempts' => 2]);

        foreach (range(1, 2) as $i) {
            $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
            $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->latest('id')->first();
            $this->submitAttempt($user, $attempt, []);
        }

        $this->assertSame(2, $exam->fresh()->attempts()->where('user_id', $user->id)->count());

        $response = $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        $response->assertRedirect(route('cbt.exams.show', $exam));
        $this->assertSame(2, $exam->fresh()->attempts()->where('user_id', $user->id)->count());
    }

    public function test_an_exam_that_has_not_opened_yet_blocks_starting(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 1, ['available_from' => now()->addDay()]);

        $response = $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        $response->assertRedirect(route('cbt.exams.show', $exam));
        $this->assertSame(0, $exam->fresh()->attempts()->count());
    }

    public function test_an_exam_that_has_closed_blocks_starting(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 1, ['available_until' => now()->subDay()]);

        $response = $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        $response->assertRedirect(route('cbt.exams.show', $exam));
        $this->assertSame(0, $exam->fresh()->attempts()->count());
    }

    public function test_an_attempt_already_in_progress_can_still_be_resumed_after_the_window_closes(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 1, ['available_until' => now()->addHour()]);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();

        // Window closes after the attempt has already started.
        $exam->update(['available_until' => now()->subMinute()]);

        $this->actingAs($user)
            ->get("/t/acme/cbt/attempts/{$attempt->id}")
            ->assertOk();
    }

    public function test_randomized_question_order_is_deterministic_per_attempt(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 8, ['randomize_questions' => true]);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();

        $firstOrder = $exam->fresh()->orderedQuestions($attempt)->pluck('id')->all();
        $secondOrder = $exam->fresh()->orderedQuestions($attempt)->pluck('id')->all();

        $this->assertSame($firstOrder, $secondOrder);
    }

    public function test_randomized_order_differs_between_two_different_attempts(): void
    {
        // Two different users, each starting their own attempt via the real
        // route — direct model creation without an HTTP request never
        // resolves a tenant, so Question queries (scoped by BelongsToTenant)
        // would silently come back empty rather than exercising real data.
        $tenant = $this->tenantWithCbt();
        $userA = User::factory()->create(['tenant_id' => $tenant->id]);
        $userB = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 8, ['randomize_questions' => true]);

        $this->actingAs($userA)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attemptA = $exam->fresh()->attempts()->where('user_id', $userA->id)->first();

        $this->actingAs($userB)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attemptB = $exam->fresh()->attempts()->where('user_id', $userB->id)->first();

        $orderA = $exam->fresh()->orderedQuestions($attemptA)->pluck('id')->all();
        $orderB = $exam->fresh()->orderedQuestions($attemptB)->pluck('id')->all();

        $this->assertNotSame($orderA, $orderB);
    }

    public function test_questions_per_attempt_limits_the_subset_shown_and_graded(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 5, ['questions_per_attempt' => 2]);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();

        $shown = $exam->fresh()->orderedQuestions($attempt);
        $this->assertCount(2, $shown);

        $answers = $shown->mapWithKeys(fn ($q) => [
            $q->id => [$q->options->firstWhere('is_correct', true)->id],
        ])->all();

        $this->submitAttempt($user, $attempt, $answers);

        // Only the 2 shown questions were graded, all answered correctly -> 100%, not diluted by the other 3 in the pool.
        $this->assertSame(100, $attempt->fresh()->score);
        $this->assertSame(2, $attempt->fresh()->answers()->count());
    }

    public function test_the_review_pane_only_lists_the_subset_shown_to_this_attempt(): void
    {
        // Regression test: the shared progress-strip partial called
        // Exam::orderedQuestions() with no $attempt argument, which always
        // falls back to the full pool — so a 2-of-5 questions_per_attempt
        // exam still showed all 5 entries in the review pane.
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 5, [
            'questions_per_attempt' => 2,
            'navigation_mode' => 'one_at_a_time',
        ]);

        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();

        $html = $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}/review")->assertOk()->getContent();

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        // The progress strip renders one <a> or <span> per question inside
        // its own container — count those directly rather than the review
        // list below it, since only the strip had the bug.
        $stripEntries = $xpath->query("//div[contains(@class, 'flex-wrap')][contains(@class, 'gap-2')][1]/*");

        $this->assertSame(2, $stripEntries->length);
    }

    public function test_the_exam_show_page_reports_the_effective_question_count(): void
    {
        $tenant = $this->tenantWithCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithQuestions($tenant, 5, ['questions_per_attempt' => 2]);

        $html = $this->actingAs($user)->get("/t/acme/cbt/exams/{$exam->slug}")->assertOk()->getContent();

        $this->assertStringContainsString('random, of 5', $html);
    }
}
