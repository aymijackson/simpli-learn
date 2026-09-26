<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ExamResultReady;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CbtWrittenQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Learner']);
    }

    private function exam(array $attributes = []): Exam
    {
        return Exam::create($attributes + [
            'tenant_id' => $this->tenant->id, 'title' => 'Mixed Quiz', 'slug' => 'mixed-quiz',
            'duration_minutes' => 30, 'pass_percentage' => 70, 'is_published' => true,
        ]);
    }

    private function question(Exam $exam, string $type, array $correct = [], array $wrong = [], int $points = 1): Question
    {
        $question = $exam->questions()->create([
            'tenant_id' => $this->tenant->id, 'question_text' => "A {$type} question",
            'position' => $exam->questions()->count(), 'answer_type' => $type, 'points' => $points,
        ]);
        foreach ($correct as $text) {
            $question->options()->create(['tenant_id' => $this->tenant->id, 'option_text' => $text, 'is_correct' => true, 'position' => 0]);
        }
        foreach ($wrong as $text) {
            $question->options()->create(['tenant_id' => $this->tenant->id, 'option_text' => $text, 'is_correct' => false, 'position' => 1]);
        }

        return $question;
    }

    private function start(Exam $exam): ExamAttempt
    {
        $this->actingAs($this->learner)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        return $exam->attempts()->where('user_id', $this->learner->id)->latest('id')->first();
    }

    public function test_true_false_and_short_answer_are_auto_graded_with_forgiving_matching(): void
    {
        $exam = $this->exam();
        $tf = $this->question($exam, 'true_false', ['True'], ['False']);
        $short = $this->question($exam, 'short_answer', ['Phishing', 'a phishing attack']);
        $attempt = $this->start($exam);

        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [
            $tf->id => $tf->options->firstWhere('option_text', 'True')->id,
            $short->id => '  A   PHISHING attack. ',
        ]]);

        $attempt->refresh();
        $this->assertFalse($attempt->needs_marking);
        $this->assertEquals(100, $attempt->score);
        $this->assertTrue($attempt->passed());
    }

    public function test_a_wrong_short_answer_scores_zero(): void
    {
        $exam = $this->exam();
        $short = $this->question($exam, 'short_answer', ['Phishing']);
        $attempt = $this->start($exam);

        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [$short->id => 'fishing']]);

        $this->assertEquals(0, $attempt->fresh()->score);
    }

    public function test_an_essay_holds_the_score_until_it_is_marked_then_emails_the_result(): void
    {
        Notification::fake();
        $exam = $this->exam(['certificate_policy' => 'free']);
        $tf = $this->question($exam, 'true_false', ['True'], ['False']);
        $essay = $this->question($exam, 'essay', points: 4);
        $attempt = $this->start($exam);

        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [
            $tf->id => $tf->options->firstWhere('option_text', 'True')->id,
            $essay->id => 'Lock your screen, use strong passwords and report suspicious emails.',
        ]]);

        $attempt->refresh();
        $this->assertTrue($attempt->needs_marking);
        $this->assertNull($attempt->score);
        $this->assertFalse($attempt->passed());
        $this->assertNull($attempt->certificate);

        $this->actingAs($this->learner)->get("/t/acme/cbt/attempts/{$attempt->id}/result")
            ->assertOk()->assertSee('awaiting marking');

        $this->actingAs($this->owner)->get('/t/acme/cbt/manage/marking')->assertOk()->assertSee('Ada Learner');
        $this->actingAs($this->owner)->get("/t/acme/cbt/manage/marking/{$attempt->id}")
            ->assertOk()->assertSee('Lock your screen, use strong passwords');

        $answer = $attempt->answers()->where('question_id', $essay->id)->first();

        // More than the question is worth is rejected.
        $this->actingAs($this->owner)->put("/t/acme/cbt/manage/marking/{$attempt->id}", ['points' => [$answer->id => 5]])
            ->assertSessionHasErrors("points.{$answer->id}");

        $this->actingAs($this->owner)->put("/t/acme/cbt/manage/marking/{$attempt->id}", [
            'points' => [$answer->id => 3], 'feedback' => [$answer->id => 'Good, but mention clean desks.'],
        ])->assertRedirect(route('cbt.manage.marking.index', ['tenant' => 'acme']));

        $attempt->refresh();
        $this->assertFalse($attempt->needs_marking);
        $this->assertEquals(80, $attempt->score); // (1 + 3) / 5
        $this->assertTrue($attempt->passed());
        $this->assertNotNull($attempt->certificate);
        $this->assertSame($this->owner->id, $answer->fresh()->marked_by_user_id);
        Notification::assertSentTo($this->learner, ExamResultReady::class);

        $this->actingAs($this->learner)->get("/t/acme/cbt/attempts/{$attempt->id}/result")
            ->assertOk()->assertSee('Good, but mention clean desks.');
    }

    public function test_a_blank_essay_scores_zero_without_needing_marking(): void
    {
        $exam = $this->exam();
        $essay = $this->question($exam, 'essay');
        $attempt = $this->start($exam);

        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [$essay->id => '   ']]);

        $attempt->refresh();
        $this->assertFalse($attempt->needs_marking);
        $this->assertEquals(0, $attempt->score);
    }

    public function test_awaiting_marking_shows_in_the_people_report(): void
    {
        $exam = $this->exam();
        $essay = $this->question($exam, 'essay');
        $attempt = $this->start($exam);
        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [$essay->id => 'My answer']]);

        $this->actingAs($this->owner)->get('/t/acme/cbt/manage/analytics/mixed-quiz/people')
            ->assertOk()->assertSeeInOrder(['Ada Learner', 'Awaiting marking']);
    }

    public function test_one_at_a_time_saves_typed_answers(): void
    {
        $exam = $this->exam(['navigation_mode' => 'one_at_a_time', 'allow_backward_navigation' => true]);
        $short = $this->question($exam, 'short_answer', ['Phishing']);
        $this->question($exam, 'essay');
        $attempt = $this->start($exam);

        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}/questions/1", ['answers' => 'phishing'])
            ->assertRedirect(route('cbt.attempts.questions.show', ['tenant' => 'acme', 'attempt' => $attempt, 'page' => 2]));

        $this->assertSame('phishing', $attempt->answers()->where('question_id', $short->id)->value('text_response'));
        $this->actingAs($this->learner)->get("/t/acme/cbt/attempts/{$attempt->id}/questions/1")->assertSee('value="phishing"', false);
    }

    public function test_members_cannot_mark(): void
    {
        $exam = $this->exam();
        $attempt = ExamAttempt::create(['tenant_id' => $this->tenant->id, 'exam_id' => $exam->id, 'user_id' => $this->learner->id, 'started_at' => now(), 'submitted_at' => now(), 'needs_marking' => true]);

        $this->actingAs($this->learner)->get('/t/acme/cbt/manage/marking')->assertForbidden();
        $this->actingAs($this->learner)->put("/t/acme/cbt/manage/marking/{$attempt->id}", [])->assertForbidden();
    }

    public function test_the_question_editor_builds_true_false_and_accepted_answers(): void
    {
        $exam = $this->exam();

        $this->actingAs($this->owner)->post('/t/acme/cbt/manage/exams/mixed-quiz/questions', [
            'question_text' => 'Sharing passwords is fine.', 'answer_type' => 'true_false', 'correct_answer' => 'false', 'position' => 0, 'points' => 1,
        ])->assertRedirect(route('cbt.manage.exams.edit', ['tenant' => 'acme', 'exam' => 'mixed-quiz']));
        $tf = $exam->questions()->where('answer_type', 'true_false')->first();
        $this->assertSame(['True' => false, 'False' => true], $tf->options->pluck('is_correct', 'option_text')->all());

        $this->actingAs($this->owner)->post('/t/acme/cbt/manage/exams/mixed-quiz/questions', [
            'question_text' => 'Name the attack.', 'answer_type' => 'short_answer', 'accepted_answers' => "Phishing\nphishing.\n\nSpear phishing", 'position' => 1,
        ])->assertSessionHasNoErrors();
        $short = $exam->questions()->where('answer_type', 'short_answer')->first();
        $this->assertSame(['Phishing', 'Spear phishing'], $short->options->pluck('option_text')->all());

        $this->actingAs($this->owner)->post('/t/acme/cbt/manage/exams/mixed-quiz/questions', [
            'question_text' => 'Explain.', 'answer_type' => 'short_answer', 'accepted_answers' => '', 'position' => 2,
        ])->assertSessionHasErrors('accepted_answers');
    }
}
