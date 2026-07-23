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

class LmsManagementTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    public function test_a_member_cannot_access_course_management(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($member)->get('/t/acme/lms/manage/courses')->assertForbidden();
    }

    public function test_an_owner_can_create_a_draft_course_invisible_to_the_public_catalog(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $response = $this->actingAs($owner)->post('/t/acme/lms/manage/courses', [
            'title' => 'Intro to Chemistry',
            'slug' => 'intro-to-chemistry',
            'description' => 'The basics.',
        ]);

        $response->assertRedirect();
        $course = Course::where('slug', 'intro-to-chemistry')->first();
        $this->assertNotNull($course);
        $this->assertFalse($course->is_published);

        $catalog = $this->actingAs($owner)->get('/t/acme/lms');
        $catalog->assertDontSee('Intro to Chemistry');
    }

    public function test_publishing_a_course_makes_it_visible_on_the_catalog(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Intro', 'slug' => 'intro', 'is_published' => false]);

        $this->actingAs($owner)->put("/t/acme/lms/manage/courses/{$course->slug}", [
            'title' => 'Intro',
            'slug' => 'intro',
            'is_published' => '1',
        ])->assertRedirect();

        $this->assertTrue($course->fresh()->is_published);
        $this->actingAs($owner)->get('/t/acme/lms')->assertSee('Intro');
    }

    public function test_an_owner_can_add_edit_and_delete_a_lesson(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Intro', 'slug' => 'intro', 'is_published' => true]);

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/{$course->slug}/lessons", [
            'title' => 'Lesson One',
            'content' => 'Some content.',
            'position' => 0,
        ])->assertRedirect();

        $lesson = $course->lessons()->first();
        $this->assertNotNull($lesson);
        $this->assertSame('Lesson One', $lesson->title);

        $this->actingAs($owner)->put("/t/acme/lms/manage/courses/{$course->slug}/lessons/{$lesson->id}", [
            'title' => 'Lesson One (Updated)',
            'content' => 'Updated content.',
            'position' => 0,
        ])->assertRedirect();

        $this->assertSame('Lesson One (Updated)', $lesson->fresh()->title);

        $this->actingAs($owner)
            ->delete("/t/acme/lms/manage/courses/{$course->slug}/lessons/{$lesson->id}")
            ->assertRedirect();

        $this->assertNull($course->lessons()->find($lesson->id));
    }

    public function test_deleting_a_course_removes_it_from_the_catalog(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Intro', 'slug' => 'intro', 'is_published' => true]);

        $this->actingAs($owner)
            ->delete("/t/acme/lms/manage/courses/{$course->slug}")
            ->assertRedirect();

        $this->assertNull(Course::find($course->id));
    }
}
