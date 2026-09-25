<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LmsCoursePricingTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    public function test_free_course_enrollment_is_unchanged(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true]);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll')->assertRedirect(route('lms.courses.show', $course));

        $this->assertTrue($course->isEnrolled($user));
    }

    public function test_a_paid_course_blocks_direct_enrollment_and_redirects_to_purchase(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $course = Course::create([
            'tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true,
            'pricing_policy' => 'paid', 'price' => 25, 'currency' => 'USD',
        ]);

        $response = $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');

        $response->assertRedirect(route('lms.courses.purchase.create', $course));
        $this->assertFalse($course->isEnrolled($user));
    }

    public function test_a_preview_lesson_is_viewable_without_enrollment_on_a_paid_course(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $course = Course::create([
            'tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true,
            'pricing_policy' => 'paid', 'price' => 25, 'currency' => 'USD',
        ]);
        $previewLesson = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'Intro', 'content' => 'a', 'position' => 0, 'is_preview' => true]);
        $lockedLesson = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'Deep dive', 'content' => 'b', 'position' => 1, 'is_preview' => false]);

        $this->actingAs($user)
            ->get("/t/acme/lms/courses/algebra/lessons/{$previewLesson->id}")
            ->assertOk();

        $this->actingAs($user)
            ->get("/t/acme/lms/courses/algebra/lessons/{$lockedLesson->id}")
            ->assertForbidden();
    }

    public function test_owner_can_set_pricing_and_preview_fields_via_the_manage_forms(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)->post('/t/acme/lms/manage/courses', [
            'title' => 'Algebra', 'slug' => 'algebra',
            'pricing_policy' => 'paid', 'price' => 25, 'currency' => 'USD',
            'certificate_policy' => 'free',
        ])->assertRedirect();

        $course = Course::where('slug', 'algebra')->first();
        $this->assertSame('paid', $course->pricing_policy->value);
        $this->assertEquals(25, $course->price);
        $this->assertSame('free', $course->certificate_policy->value);

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/{$course->slug}/lessons", [
            'title' => 'Intro', 'content' => 'Hello', 'position' => 0, 'is_preview' => '1',
        ])->assertRedirect();

        $lesson = $course->fresh()->lessons()->first();
        $this->assertTrue($lesson->is_preview);
    }

    public function test_modules_are_manageable_regardless_of_assessment_mode(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true, 'assessment_mode' => 'none']);

        $html = $this->actingAs($owner)->get('/t/acme/lms/manage/courses/algebra/edit')->assertOk()->getContent();
        $this->assertStringContainsString('New module title', $html);

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/algebra/modules", [
            'title' => 'Module 1', 'position' => 0,
        ])->assertRedirect();

        $this->assertSame(1, $course->fresh()->modules()->count());
    }
}
