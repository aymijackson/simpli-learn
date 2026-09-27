<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the structural accessibility fixes (found with axe-core): skip
 * links, labelled fields, grouped exam answers and an announced timer.
 */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_sign_in_fields_are_labelled_and_the_page_has_landmarks(): void
    {
        $html = $this->get('/t/acme/login')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<label for="(field-email-\w+)"[^>]*>.*?<input[^>]*id="\1"/s', $html);
        $this->assertMatchesRegularExpression('/<label for="(field-password-\w+)"[^>]*>.*?<input[^>]*id="\1"/s', $html);
        $this->assertStringContainsString('<main id="main-content"', $html);
        $this->assertStringContainsString('Skip to content', $html);
    }

    public function test_every_signed_in_page_has_a_skip_link_to_the_main_content(): void
    {
        $this->actingAs($this->learner)->get('/t/acme')
            ->assertSee('<a href="#main-content"', false)
            ->assertSee('<main id="main-content" tabindex="-1"', false);
    }

    public function test_field_errors_are_tied_to_their_field(): void
    {
        $this->from('/t/acme/login')->post('/t/acme/login', ['email' => '', 'password' => '']);
        $html = $this->get('/t/acme/login')->getContent();

        $this->assertMatchesRegularExpression('/id="(field-email-\w+)"[^>]*aria-invalid="true" aria-describedby="\1-error"/s', $html);
    }

    public function test_exam_answers_are_grouped_under_their_question_and_the_timer_is_announced(): void
    {
        $exam = Exam::create(['tenant_id' => $this->tenant->id, 'title' => 'Quiz', 'slug' => 'quiz', 'duration_minutes' => 20, 'pass_percentage' => 50, 'is_published' => true]);
        $question = $exam->questions()->create(['tenant_id' => $this->tenant->id, 'question_text' => 'Pick one', 'position' => 0]);
        $question->options()->create(['tenant_id' => $this->tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);
        $exam->questions()->create(['tenant_id' => $this->tenant->id, 'question_text' => 'Explain', 'position' => 1, 'answer_type' => 'essay']);

        $this->actingAs($this->learner)->post('/t/acme/cbt/exams/quiz/start');
        $attempt = $exam->attempts()->withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->learner)->get("/t/acme/cbt/attempts/{$attempt->id}")
            ->assertOk()
            ->assertSee("role=\"group\" aria-labelledby=\"q-{$question->id}-number q-{$question->id}-text\"", false)
            ->assertSee('question 1 for review')
            ->assertSee('role="timer" aria-label="Time remaining"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('aria-labelledby="q-', false); // the essay box is named by its question
    }
}
