<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CoursePurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LmsCourseCertificatesTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function courseWithOneLesson(Tenant $tenant, array $attributes = []): array
    {
        $course = Course::create(array_merge([
            'tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true,
        ], $attributes));
        $lesson = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'Intro', 'content' => 'a', 'position' => 0]);

        return [$course, $lesson];
    }

    public function test_a_free_certificate_policy_auto_issues_on_lesson_completion(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$course, $lesson] = $this->courseWithOneLesson($tenant, ['certificate_policy' => 'free']);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');
        $this->actingAs($user)->post("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/complete");

        $this->assertNotNull($course->fresh()->certificateFor($user->fresh()));
    }

    public function test_a_free_certificate_policy_auto_issues_via_the_course_final_exam_pass_lazy_check(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $exam = \Elibrary\Cbt\Models\Exam::create([
            'tenant_id' => $tenant->id, 'title' => 'Final', 'slug' => 'final',
            'duration_minutes' => 30, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $correct = $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        [$course, $lesson] = $this->courseWithOneLesson($tenant, [
            'certificate_policy' => 'free', 'assessment_mode' => 'course_final', 'final_exam_id' => $exam->id,
        ]);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');
        $this->actingAs($user)->post("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/complete");

        // Lesson complete but exam not passed yet -> no certificate.
        $this->assertNull($course->fresh()->certificateFor($user->fresh()));

        $this->actingAs($user)->post('/t/acme/cbt/exams/final/start');
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [$question->id => $correct->id]]);

        // Viewing the course page after passing the exam is what triggers issuance.
        $this->actingAs($user)->get('/t/acme/lms/courses/algebra')->assertOk();

        $this->assertNotNull($course->fresh()->certificateFor($user->fresh()));
    }

    public function test_a_paid_certificate_policy_issues_nothing_until_purchase_confirms(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->actingAs($owner)->put('/t/acme/lms/manage/payment-gateways/bank_transfer', ['is_enabled' => '1']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$course, $lesson] = $this->courseWithOneLesson($tenant, [
            'certificate_policy' => 'paid', 'certificate_price' => 5, 'certificate_currency' => 'USD',
        ]);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');
        $this->actingAs($user)->post("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/complete");

        $this->assertNull($course->fresh()->certificateFor($user->fresh()));

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/certificate/purchase', ['gateway' => 'bank_transfer'])->assertRedirect();
        $purchase = CoursePurchase::where('course_id', $course->id)->where('purchase_type', 'certificate')->first();
        $this->assertNotNull($purchase);

        $this->actingAs($owner)->post("/t/acme/lms/manage/course-purchases/{$purchase->id}/confirm")->assertRedirect();

        $this->assertNotNull($course->fresh()->certificateFor($user->fresh()));
    }

    public function test_the_public_verification_page_resolves_a_valid_token_without_any_tenant_context(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Jane Learner']);
        [$course, $lesson] = $this->courseWithOneLesson($tenant, ['certificate_policy' => 'free']);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');
        $this->actingAs($user)->post("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/complete");

        $token = $course->fresh()->certificateFor($user->fresh())->verification_token;

        $response = $this->get("/course-certificates/verify/{$token}");

        $response->assertOk();
        $response->assertSee('Jane Learner');
        $response->assertSee('Verified certificate', escape: false);
    }

    public function test_a_none_certificate_policy_shows_no_certificate_ui(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        [$course, $lesson] = $this->courseWithOneLesson($tenant, ['certificate_policy' => 'none']);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');
        $this->actingAs($user)->post("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/complete");

        $this->assertNull($course->fresh()->certificateFor($user->fresh()));

        $html = $this->actingAs($user)->get('/t/acme/lms/courses/algebra')->assertOk()->getContent();
        $this->assertStringNotContainsString('Download certificate', $html);
        $this->assertStringNotContainsString('Get certificate', $html);
    }
}
