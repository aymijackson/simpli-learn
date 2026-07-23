<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LmsTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithLms(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);

        return $tenant;
    }

    public function test_catalog_lists_only_published_courses(): void
    {
        $tenant = $this->tenantWithLms();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Course::create(['tenant_id' => $tenant->id, 'title' => 'Published Course', 'slug' => 'published-course', 'is_published' => true]);
        Course::create(['tenant_id' => $tenant->id, 'title' => 'Draft Course', 'slug' => 'draft-course', 'is_published' => false]);

        $response = $this->actingAs($user)->get('/t/acme/lms');

        $response->assertOk();
        $response->assertSee('Published Course');
        $response->assertDontSee('Draft Course');
    }

    public function test_a_user_must_enroll_before_viewing_lessons(): void
    {
        $tenant = $this->tenantWithLms();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true]);
        $lesson = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'Variables', 'content' => 'content', 'position' => 0]);

        $this->actingAs($user)
            ->get("/t/acme/lms/courses/algebra/lessons/{$lesson->id}")
            ->assertForbidden();

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');

        $this->actingAs($user)
            ->get("/t/acme/lms/courses/algebra/lessons/{$lesson->id}")
            ->assertOk();
    }

    public function test_marking_a_lesson_complete_updates_course_progress(): void
    {
        $tenant = $this->tenantWithLms();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true]);
        $lessonOne = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'Lesson 1', 'content' => 'a', 'position' => 0]);
        $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'Lesson 2', 'content' => 'b', 'position' => 1]);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');

        $this->assertSame(0, $course->progressPercentFor($user));

        $this->actingAs($user)->post("/t/acme/lms/courses/algebra/lessons/{$lessonOne->id}/complete");

        $this->assertSame(50, $course->fresh()->progressPercentFor($user));
    }

    public function test_a_tenant_without_the_lms_module_gets_a_403(): void
    {
        $tenant = Tenant::create(['name' => 'Bright CBT', 'slug' => 'brightcbt', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/t/brightcbt/lms')->assertForbidden();
    }
}
