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

class CbtManagementTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    public function test_a_member_cannot_access_exam_management(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($member)->get('/t/acme/cbt/manage/exams')->assertForbidden();
    }

    public function test_the_exam_edit_page_renders(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $exam->sections()->create(['tenant_id' => $tenant->id, 'title' => 'Warm-up', 'position' => 0]);
        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);

        $this->actingAs($owner)
            ->get("/t/acme/cbt/manage/exams/{$exam->slug}/edit")
            ->assertOk()
            ->assertSee('Warm-up')
            ->assertSee('Sections');
    }

    public function test_an_owner_can_create_a_draft_exam_invisible_to_the_public_catalog(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)->post('/t/acme/cbt/manage/exams', [
            'title' => 'Geography Quiz',
            'slug' => 'geography-quiz',
            'duration_minutes' => 20,
            'pass_percentage' => 60,
        ])->assertRedirect();

        $exam = Exam::where('slug', 'geography-quiz')->first();
        $this->assertNotNull($exam);
        $this->assertFalse($exam->is_published);

        $this->actingAs($owner)->get('/t/acme/cbt')->assertDontSee('Geography Quiz');
    }

    public function test_an_owner_can_build_a_question_with_options_and_learners_can_take_it(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Geography Quiz',
            'slug' => 'geography-quiz',
            'duration_minutes' => 20,
            'pass_percentage' => 50,
            'is_published' => true,
        ]);

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions", [
            'question_text' => 'What is the capital of France?',
            'position' => 0,
        ])->assertRedirect();

        $question = $exam->questions()->first();
        $this->assertNotNull($question);

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/{$question->id}/options", [
            'option_text' => 'Paris',
        ])->assertRedirect();

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/{$question->id}/options", [
            'option_text' => 'London',
        ])->assertRedirect();

        $options = $question->options()->get();
        $this->assertCount(2, $options);
        $paris = $options->firstWhere('option_text', 'Paris');

        $this->actingAs($owner)->put("/t/acme/cbt/manage/exams/{$exam->slug}/questions/{$question->id}/options/{$paris->id}", [
            'option_text' => 'Paris',
            'is_correct' => '1',
        ])->assertRedirect();

        $this->assertTrue($paris->fresh()->is_correct);
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());

        // A learner can now take the exam and be graded against the option the owner just built.
        $learner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);
        $this->actingAs($learner)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->attempts()->where('user_id', $learner->id)->first();

        $this->actingAs($learner)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => $paris->id],
        ]);

        $this->assertSame(100, $attempt->fresh()->score);
    }

    public function test_marking_a_second_option_correct_keeps_both_correct_for_a_multiple_answer_question(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->create([
            'tenant_id' => $tenant->id, 'question_text' => 'Q1', 'answer_type' => 'multiple', 'position' => 0,
        ]);
        $optionA = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);
        $optionB = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'B', 'is_correct' => false, 'position' => 1]);

        $this->actingAs($owner)->put("/t/acme/cbt/manage/exams/{$exam->slug}/questions/{$question->id}/options/{$optionB->id}", [
            'option_text' => 'B',
            'is_correct' => '1',
        ])->assertRedirect();

        $this->assertTrue($optionA->fresh()->is_correct);
        $this->assertTrue($optionB->fresh()->is_correct);
    }

    public function test_marking_a_second_option_correct_unmarks_the_first_for_a_single_answer_question(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->create([
            'tenant_id' => $tenant->id, 'question_text' => 'Q1', 'answer_type' => 'single', 'position' => 0,
        ]);
        $optionA = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);
        $optionB = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'B', 'is_correct' => false, 'position' => 1]);

        $this->actingAs($owner)->put("/t/acme/cbt/manage/exams/{$exam->slug}/questions/{$question->id}/options/{$optionB->id}", [
            'option_text' => 'B',
            'is_correct' => '1',
        ])->assertRedirect();

        $this->assertFalse($optionA->fresh()->is_correct);
        $this->assertTrue($optionB->fresh()->is_correct);
    }

    public function test_unchecking_correct_only_unmarks_that_option(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $optionA = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);
        $optionB = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'B', 'is_correct' => true, 'position' => 1]);

        $this->actingAs($owner)->put("/t/acme/cbt/manage/exams/{$exam->slug}/questions/{$question->id}/options/{$optionA->id}", [
            'option_text' => 'A',
        ])->assertRedirect();

        $this->assertFalse($optionA->fresh()->is_correct);
        $this->assertTrue($optionB->fresh()->is_correct);
    }

    public function test_the_option_save_button_is_actually_inside_its_update_form(): void
    {
        // Regression test: a <form> was previously nested inside the option's
        // update <form> (for the delete button). Nested forms are invalid
        // HTML — browsers resolve the markup by detaching everything after
        // the inner form's closing tag (including the Save button) from any
        // form at all, so clicking Save silently did nothing.
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $option = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);

        $html = $this->actingAs($owner)
            ->get("/t/acme/cbt/manage/exams/{$exam->slug}/questions/{$question->id}/edit")
            ->getContent();

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $updateForm = null;
        foreach ($dom->getElementsByTagName('form') as $form) {
            if (! str_contains($form->getAttribute('action'), "/options/{$option->id}")) {
                continue;
            }
            $method = $xpath->query('.//input[@name="_method"]', $form);
            if ($method->length && strtoupper($method->item(0)->getAttribute('value')) === 'PUT') {
                $updateForm = $form;
                break;
            }
        }

        $this->assertNotNull($updateForm, 'Could not find the option update <form> in the rendered page.');

        $saveButtons = $xpath->query('.//button[contains(., "Save")]', $updateForm);
        $this->assertGreaterThan(0, $saveButtons->length, 'The Save button is not inside the option update <form>.');
    }

    public function test_an_owner_can_delete_a_question_and_an_exam(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);

        $this->actingAs($owner)
            ->delete("/t/acme/cbt/manage/exams/{$exam->slug}/questions/{$question->id}")
            ->assertRedirect();
        $this->assertNull($exam->questions()->find($question->id));

        $this->actingAs($owner)
            ->delete("/t/acme/cbt/manage/exams/{$exam->slug}")
            ->assertRedirect();
        $this->assertNull(Exam::find($exam->id));
    }
}
