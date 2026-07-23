<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LmsExamGatingTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithLmsAndCbt(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);

        return $tenant;
    }

    private function singleQuestionExam(Tenant $tenant, string $title, int $passPercentage = 50): Exam
    {
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'duration_minutes' => 30,
            'pass_percentage' => $passPercentage,
            'is_published' => true,
        ]);

        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q', 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        return $exam;
    }

    private function passExam(User $user, Exam $exam): void
    {
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();
        $correctOptionId = $exam->questions->first()->options->firstWhere('is_correct', true)->id;

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$exam->questions->first()->id => [$correctOptionId]],
        ]);
    }

    public function test_per_lesson_mode_locks_the_next_lesson_until_the_gating_exam_is_passed(): void
    {
        $tenant = $this->tenantWithLmsAndCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->singleQuestionExam($tenant, 'Lesson 1 Quiz');

        $course = Course::create([
            'tenant_id' => $tenant->id, 'title' => 'Course', 'slug' => 'course',
            'is_published' => true, 'assessment_mode' => 'per_lesson',
        ]);
        $lesson1 = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'L1', 'content' => 'a', 'position' => 0, 'exam_id' => $exam->id]);
        $lesson2 = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'L2', 'content' => 'b', 'position' => 1]);

        $this->actingAs($user)->post('/t/acme/lms/courses/course/enroll');

        // Lesson 2 is locked before lesson 1 is even completed.
        $this->actingAs($user)->get("/t/acme/lms/courses/course/lessons/{$lesson2->id}")->assertForbidden();

        $this->actingAs($user)->post("/t/acme/lms/courses/course/lessons/{$lesson1->id}/complete");

        // Still locked: lesson 1 done, but its gating exam hasn't been passed yet.
        $this->actingAs($user)->get("/t/acme/lms/courses/course/lessons/{$lesson2->id}")->assertForbidden();
        $this->assertFalse($lesson2->fresh()->isUnlockedFor($user->fresh()));

        $this->passExam($user, $exam);

        $this->assertTrue($lesson2->fresh()->isUnlockedFor($user->fresh()));
        $this->actingAs($user)->get("/t/acme/lms/courses/course/lessons/{$lesson2->id}")->assertOk();
    }

    public function test_per_module_mode_locks_the_next_module_until_its_gating_exam_is_passed(): void
    {
        $tenant = $this->tenantWithLmsAndCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->singleQuestionExam($tenant, 'Module 1 Quiz');

        $course = Course::create([
            'tenant_id' => $tenant->id, 'title' => 'Course', 'slug' => 'course',
            'is_published' => true, 'assessment_mode' => 'per_module',
        ]);
        $moduleA = $course->modules()->create(['tenant_id' => $tenant->id, 'title' => 'Module A', 'position' => 0, 'exam_id' => $exam->id]);
        $moduleB = $course->modules()->create(['tenant_id' => $tenant->id, 'title' => 'Module B', 'position' => 1]);

        $lesson1 = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'L1', 'content' => 'a', 'position' => 0, 'course_module_id' => $moduleA->id]);
        $lesson2 = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'L2', 'content' => 'b', 'position' => 1, 'course_module_id' => $moduleB->id]);
        $unassigned = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'L3', 'content' => 'c', 'position' => 2]);

        $this->actingAs($user)->post('/t/acme/lms/courses/course/enroll');

        $this->assertFalse($lesson2->fresh()->isUnlockedFor($user->fresh()));
        // A lesson with no module assigned is never gated, even under per_module mode.
        $this->assertTrue($unassigned->fresh()->isUnlockedFor($user->fresh()));

        $this->actingAs($user)->post("/t/acme/lms/courses/course/lessons/{$lesson1->id}/complete");
        $this->assertFalse($lesson2->fresh()->isUnlockedFor($user->fresh()));

        $this->passExam($user, $exam);

        $this->assertTrue($lesson2->fresh()->isUnlockedFor($user->fresh()));
        $this->actingAs($user)->get("/t/acme/lms/courses/course/lessons/{$lesson2->id}")->assertOk();
    }

    public function test_course_final_mode_leaves_lessons_unlocked_but_requires_the_final_exam_to_pass(): void
    {
        $tenant = $this->tenantWithLmsAndCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->singleQuestionExam($tenant, 'Final Exam');

        $course = Course::create([
            'tenant_id' => $tenant->id, 'title' => 'Course', 'slug' => 'course',
            'is_published' => true, 'assessment_mode' => 'course_final', 'final_exam_id' => $exam->id,
        ]);
        $lesson = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'L1', 'content' => 'a', 'position' => 0]);

        $this->actingAs($user)->post('/t/acme/lms/courses/course/enroll');

        // Lesson access is never gated in this mode.
        $this->actingAs($user)->get("/t/acme/lms/courses/course/lessons/{$lesson->id}")->assertOk();

        $this->actingAs($user)->post("/t/acme/lms/courses/course/lessons/{$lesson->id}/complete");

        // 100% lesson completion, but the course isn't "passed" without the final exam.
        $this->assertTrue($course->fresh()->isCompletedBy($user->fresh()));
        $this->assertFalse($course->fresh()->isPassedBy($user->fresh()));

        $this->passExam($user, $exam);

        $this->assertTrue($course->fresh()->isPassedBy($user->fresh()));
    }

    public function test_none_mode_never_gates_lessons_regardless_of_exam_assignment(): void
    {
        $tenant = $this->tenantWithLmsAndCbt();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->singleQuestionExam($tenant, 'Unused Quiz');

        $course = Course::create([
            'tenant_id' => $tenant->id, 'title' => 'Course', 'slug' => 'course',
            'is_published' => true, 'assessment_mode' => 'none',
        ]);
        $lesson1 = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'L1', 'content' => 'a', 'position' => 0, 'exam_id' => $exam->id]);
        $lesson2 = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'L2', 'content' => 'b', 'position' => 1]);

        $this->actingAs($user)->post('/t/acme/lms/courses/course/enroll');

        // Lesson 2 is reachable even though lesson 1 (which has an exam attached)
        // hasn't been completed — assessment_mode=none never gates on it.
        $this->actingAs($user)->get("/t/acme/lms/courses/course/lessons/{$lesson2->id}")->assertOk();
    }
}
