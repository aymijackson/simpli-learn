<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AttemptNeedsMarking;
use App\Notifications\InstructorAssigned;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CourseInstructorsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $teacher;

    private User $learner;

    private Course $taught;

    private Course $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->teacher = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Tola Teacher', 'bio' => 'Security lead for ten years.']);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->taught = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Taught Course', 'slug' => 'taught', 'is_published' => true]);
        $this->taught->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'First', 'content' => 'x', 'position' => 0]);
        $this->other = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Other Course', 'slug' => 'other', 'is_published' => true]);
        $this->other->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'Elsewhere', 'content' => 'x', 'position' => 0]);
    }

    private function assignTeacher(): void
    {
        $this->actingAs($this->owner)->put('/t/acme/lms/manage/courses/taught/instructors', ['instructor_ids' => [$this->teacher->id]])
            ->assertRedirect(route('lms.manage.courses.edit', ['tenant' => 'acme', 'course' => 'taught']));
    }

    public function test_owners_assign_instructors_who_are_told(): void
    {
        Notification::fake();
        $this->assignTeacher();

        $this->assertSame([$this->teacher->id], $this->taught->instructors()->pluck('users.id')->all());
        Notification::assertSentTo($this->teacher, InstructorAssigned::class);

        // Re-saving doesn't notify again.
        Notification::fake();
        $this->assignTeacher();
        Notification::assertNothingSent();
    }

    public function test_instructors_manage_only_the_content_of_their_courses(): void
    {
        $this->assignTeacher();
        $lesson = Lesson::withoutGlobalScopes()->where('course_id', $this->taught->id)->first();

        $this->actingAs($this->teacher)->get('/t/acme/lms/manage/courses')
            ->assertOk()->assertSee('My courses')->assertSee('Taught Course')->assertDontSee('Other Course')->assertDontSee('New course');
        $this->actingAs($this->teacher)->get('/t/acme/lms/manage/courses/taught/edit')
            ->assertOk()->assertSee('You teach this course')->assertDontSee('Save instructors')->assertDontSee('Pricing');

        $exam = Exam::create(['tenant_id' => $this->tenant->id, 'title' => 'Some exam', 'slug' => 'some-exam', 'duration_minutes' => 5, 'pass_percentage' => 50]);
        $this->actingAs($this->teacher)->post('/t/acme/lms/manage/courses/taught/lessons', [
            'title' => 'Second', 'content' => 'Body', 'position' => 1, 'exam_id' => $exam->id,
        ])->assertRedirect();
        $second = Lesson::withoutGlobalScopes()->where('title', 'Second')->firstOrFail();
        $this->assertNull($second->exam_id); // exams are the owner's call

        $this->actingAs($this->teacher)->put("/t/acme/lms/manage/courses/taught/lessons/{$lesson->id}", ['title' => 'First, revised', 'content' => 'x', 'position' => 0])
            ->assertRedirect();
        $this->assertSame('First, revised', $lesson->fresh()->title);

        // Not their course, and not owner-only settings.
        $this->actingAs($this->teacher)->get('/t/acme/lms/manage/courses/other/edit')->assertForbidden();
        $this->actingAs($this->teacher)->post('/t/acme/lms/manage/courses/other/lessons', ['title' => 'Sneaky', 'content' => 'x', 'position' => 1])->assertForbidden();
        $this->actingAs($this->teacher)->put('/t/acme/lms/manage/courses/taught', ['title' => 'Renamed', 'slug' => 'taught'])->assertForbidden();
        $this->actingAs($this->teacher)->delete('/t/acme/lms/manage/courses/taught')->assertForbidden();
        $this->actingAs($this->teacher)->put('/t/acme/lms/manage/courses/taught/instructors', ['instructor_ids' => []])->assertForbidden();
        $this->actingAs($this->teacher)->get('/t/acme/manage')->assertForbidden();
        $this->actingAs($this->teacher)->get('/t/acme/cbt/manage/exams')->assertForbidden();
    }

    public function test_instructors_see_their_course_report_but_cannot_assign(): void
    {
        $this->assignTeacher();

        $this->actingAs($this->teacher)->get('/t/acme/lms/manage/courses/taught/report')->assertOk()->assertDontSee('Assign this course');
        $this->actingAs($this->teacher)->get('/t/acme/lms/manage/courses/other/report')->assertForbidden();
        $this->actingAs($this->teacher)->post('/t/acme/lms/manage/courses/taught/assignments', ['who' => 'everyone'])->assertForbidden();
    }

    public function test_instructors_mark_only_essays_from_their_courses_and_are_told_about_them(): void
    {
        Notification::fake();
        $this->assignTeacher();
        $mine = $this->essayExam('Taught final', 'taught-final');
        $theirs = $this->essayExam('Other final', 'other-final');
        $this->taught->forceFill(['assessment_mode' => 'course_final', 'final_exam_id' => $mine->id])->save();

        $this->actingAs($this->learner)->post('/t/acme/lms/courses/taught/enroll');
        $mineAttempt = $this->submitEssay($mine);
        $theirsAttempt = $this->submitEssay($theirs);

        Notification::assertSentTo($this->teacher, AttemptNeedsMarking::class, fn ($n) => $n->attempt->id === $mineAttempt->id);
        Notification::assertNotSentTo($this->teacher, AttemptNeedsMarking::class, fn ($n) => $n->attempt->id === $theirsAttempt->id);

        $this->actingAs($this->teacher)->get('/t/acme/cbt/manage/marking')->assertOk()->assertSee('Taught final')->assertDontSee('Other final');
        $this->actingAs($this->teacher)->get("/t/acme/cbt/manage/marking/{$mineAttempt->id}")->assertOk();
        $this->actingAs($this->teacher)->get("/t/acme/cbt/manage/marking/{$theirsAttempt->id}")->assertForbidden();
        $this->actingAs($this->teacher)->put("/t/acme/cbt/manage/marking/{$theirsAttempt->id}", [])->assertForbidden();

        // Owners still see everything.
        $this->actingAs($this->owner)->get('/t/acme/cbt/manage/marking')->assertSee('Taught final')->assertSee('Other final');
    }

    public function test_learners_see_who_teaches_and_members_without_courses_get_no_back_office(): void
    {
        $this->assignTeacher();

        $this->actingAs($this->learner)->get('/t/acme/lms/courses/taught')
            ->assertOk()->assertSee('Taught by')->assertSee('Tola Teacher')->assertSee('Security lead for ten years.');
        $this->actingAs($this->teacher)->get('/t/acme')->assertSee('Teaching');

        $this->actingAs($this->learner)->get('/t/acme')->assertDontSee('>Teaching<', false);
        $this->actingAs($this->learner)->get('/t/acme/lms/manage/courses')->assertForbidden();
        $this->actingAs($this->learner)->get('/t/acme/cbt/manage/marking')->assertForbidden();
    }

    public function test_people_add_a_bio_on_their_profile(): void
    {
        $this->actingAs($this->teacher)->put('/t/acme/profile', ['name' => 'Tola Teacher', 'email' => $this->teacher->email, 'bio' => 'Now teaching privacy.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Now teaching privacy.', $this->teacher->fresh()->bio);
    }

    private function essayExam(string $title, string $slug): Exam
    {
        $exam = Exam::create(['tenant_id' => $this->tenant->id, 'title' => $title, 'slug' => $slug, 'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true]);
        $exam->questions()->create(['tenant_id' => $this->tenant->id, 'question_text' => 'Explain', 'position' => 0, 'answer_type' => 'essay']);

        return $exam;
    }

    private function submitEssay(Exam $exam): ExamAttempt
    {
        $this->actingAs($this->learner)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = ExamAttempt::withoutGlobalScopes()->where('exam_id', $exam->id)->where('user_id', $this->learner->id)->firstOrFail();
        $question = $exam->questions()->withoutGlobalScopes()->first();
        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [$question->id => 'My essay.']]);

        return $attempt->fresh();
    }
}
