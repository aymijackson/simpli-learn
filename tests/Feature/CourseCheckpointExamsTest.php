<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseCheckpointExamsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $learner;

    private Course $course;

    private Lesson $lesson1;

    private Lesson $lesson2;

    private Exam $quiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->quiz = $this->exam('Passwords Quiz');
        $this->course = Course::create([
            'tenant_id' => $this->tenant->id, 'title' => 'Data Security', 'slug' => 'data-security',
            'is_published' => true, 'assessment_mode' => 'per_lesson',
        ]);
        $this->lesson1 = $this->course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'Strong passwords', 'content' => 'a', 'position' => 0]);
        $this->lesson2 = $this->course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'Spotting phishing', 'content' => 'b', 'position' => 1, 'exam_id' => $this->quiz->id]);
        // Lesson 3 unlocks only once the quiz after lesson 2 is passed.
        $this->course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'Reporting incidents', 'content' => 'c', 'position' => 2]);
    }

    private function exam(string $title): Exam
    {
        $exam = Exam::create([
            'tenant_id' => $this->tenant->id, 'title' => $title, 'slug' => Str::slug($title),
            'duration_minutes' => 30, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->create(['tenant_id' => $this->tenant->id, 'question_text' => 'Q', 'position' => 0]);
        $question->options()->create(['tenant_id' => $this->tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['tenant_id' => $this->tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        return $exam;
    }

    private function enrol(): void
    {
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/data-security/enroll');
    }

    private function pass(Exam $exam): int
    {
        $this->actingAs($this->learner)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->attempts()->where('user_id', $this->learner->id)->latest('id')->firstOrFail();
        $question = $exam->questions->first();
        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => [$question->options->firstWhere('is_correct', true)->id]],
        ]);

        return $attempt->id;
    }

    public function test_course_quizzes_are_not_listed_as_standalone_exams(): void
    {
        $this->exam('General Knowledge');

        $this->actingAs($this->learner)->get('/t/acme/cbt')
            ->assertOk()->assertSee('General Knowledge')->assertDontSee('Passwords Quiz');
        $this->actingAs($this->learner)->get('/t/acme/search?q=Quiz')->assertOk()->assertDontSee('Passwords Quiz');
    }

    public function test_an_exam_attached_but_unused_by_the_assessment_mode_stays_standalone(): void
    {
        $this->course->update(['assessment_mode' => 'none']);

        $this->actingAs($this->learner)->get('/t/acme/cbt')->assertSee('Passwords Quiz');
    }

    public function test_people_outside_the_course_cannot_start_it(): void
    {
        $this->actingAs($this->learner)->get('/t/acme/cbt/exams/passwords-quiz')
            ->assertOk()->assertSee('Enrol in the course to take it', false);

        $this->actingAs($this->learner)->post('/t/acme/cbt/exams/passwords-quiz/start')
            ->assertRedirect(route('cbt.exams.show', ['tenant' => 'acme', 'exam' => 'passwords-quiz']));
        $this->assertSame(0, $this->quiz->attempts()->count());
    }

    public function test_enrolled_learners_must_reach_the_quiz_first(): void
    {
        $this->enrol();

        $this->actingAs($this->learner)->post('/t/acme/cbt/exams/passwords-quiz/start');
        $this->assertSame(0, $this->quiz->attempts()->count());

        $this->actingAs($this->learner)->post("/t/acme/lms/courses/data-security/lessons/{$this->lesson1->id}/complete");
        $this->actingAs($this->learner)->post('/t/acme/cbt/exams/passwords-quiz/start');
        $this->assertSame(1, $this->quiz->attempts()->count());
    }

    public function test_owners_can_try_course_quizzes_without_enrolling(): void
    {
        $owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)->post('/t/acme/cbt/exams/passwords-quiz/start');
        $this->assertSame(1, $this->quiz->attempts()->count());
    }

    public function test_the_course_outline_shows_the_quiz_in_place_and_tracks_it(): void
    {
        $this->enrol();
        $this->actingAs($this->learner)->get('/t/acme/lms/courses/data-security')
            ->assertOk()->assertSeeInOrder(['Spotting phishing', 'Lesson quiz', 'Passwords Quiz', 'Unlocks as you progress', 'Reporting incidents']);

        $this->actingAs($this->learner)->post("/t/acme/lms/courses/data-security/lessons/{$this->lesson1->id}/complete");
        $this->actingAs($this->learner)->post("/t/acme/lms/courses/data-security/lessons/{$this->lesson2->id}/complete");

        // Lesson 3 is locked behind the quiz, so the main button points at it.
        $this->actingAs($this->learner)->get('/t/acme/lms/courses/data-security')
            ->assertSee('Take the lesson quiz')->assertSee(route('cbt.exams.show', ['tenant' => 'acme', 'exam' => 'passwords-quiz']), false);

        // The lesson page offers the quiz rather than a locked "Next lesson".
        $this->actingAs($this->learner)->get("/t/acme/lms/courses/data-security/lessons/{$this->lesson2->id}")
            ->assertSee('Take the lesson quiz')->assertDontSee('Next lesson');

        $attemptId = $this->pass($this->quiz);

        $this->actingAs($this->learner)->get("/t/acme/cbt/attempts/{$attemptId}/result")
            ->assertOk()->assertSee('Continue: Reporting incidents');
        $this->actingAs($this->learner)->get('/t/acme/lms/courses/data-security')
            ->assertSeeInOrder(['Passwords Quiz', 'Passed', 'Reporting incidents']);
        $this->actingAs($this->learner)->get("/t/acme/lms/courses/data-security/lessons/{$this->lesson2->id}")
            ->assertSee('Next lesson');
    }

    public function test_a_module_test_appears_at_the_end_of_its_module(): void
    {
        $this->course->update(['assessment_mode' => 'per_module']);
        $this->lesson2->update(['exam_id' => null]);
        $module = $this->course->modules()->create(['tenant_id' => $this->tenant->id, 'title' => 'Basics', 'position' => 0, 'exam_id' => $this->quiz->id]);
        $this->lesson1->update(['course_module_id' => $module->id]);
        $this->lesson2->update(['course_module_id' => $module->id]);
        $this->enrol();

        $this->actingAs($this->learner)->get('/t/acme/lms/courses/data-security')
            ->assertSeeInOrder(['Basics', 'Strong passwords', 'Spotting phishing', 'Module test', 'Passwords Quiz', 'Reporting incidents']);

        // Reachable as soon as the module is open.
        $this->actingAs($this->learner)->post('/t/acme/cbt/exams/passwords-quiz/start');
        $this->assertSame(1, $this->quiz->attempts()->count());
    }

    public function test_the_exam_page_links_back_to_its_course(): void
    {
        $this->enrol();

        $this->actingAs($this->learner)->get('/t/acme/cbt/exams/passwords-quiz')
            ->assertOk()->assertSee('Lesson quiz')->assertSee(route('lms.courses.show', ['tenant' => 'acme', 'course' => 'data-security']), false);
    }

    public function test_without_the_lms_module_every_exam_is_standalone(): void
    {
        $this->tenant->tenantModules()->where('module', Module::Lms)->update(['is_enabled' => false]);

        $this->actingAs($this->learner)->get('/t/acme/cbt')->assertSee('Passwords Quiz');
        $this->actingAs($this->learner)->post('/t/acme/cbt/exams/passwords-quiz/start');
        $this->assertSame(1, $this->quiz->attempts()->count());
    }
}
