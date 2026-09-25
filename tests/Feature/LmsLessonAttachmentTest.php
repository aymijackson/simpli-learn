<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LmsLessonAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function courseWithLesson(Tenant $tenant): array
    {
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true]);
        $lesson = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'Intro', 'content' => 'a', 'position' => 0]);

        return [$course, $lesson];
    }

    public function test_owner_can_upload_both_access_levels(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        [$course, $lesson] = $this->courseWithLesson($tenant);

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/algebra/lessons/{$lesson->id}/attachments", [
            'title' => 'Slides', 'type' => 'file', 'access_level' => 'open',
            'file' => UploadedFile::fake()->create('slides.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/algebra/lessons/{$lesson->id}/attachments", [
            'title' => 'Lecture', 'type' => 'video', 'access_level' => 'secure',
            'file' => UploadedFile::fake()->create('lecture.mp4', 100, 'video/mp4'),
        ])->assertRedirect();

        $this->assertSame(2, $lesson->fresh()->attachments()->count());
    }

    public function test_an_open_attachment_is_downloadable_directly(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        [$course, $lesson] = $this->courseWithLesson($tenant);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/algebra/lessons/{$lesson->id}/attachments", [
            'title' => 'Slides', 'type' => 'file', 'access_level' => 'open',
            'file' => UploadedFile::fake()->create('slides.pdf', 100, 'application/pdf'),
        ]);
        $attachment = $lesson->fresh()->attachments()->first();

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/enroll');

        $this->actingAs($user)
            ->get("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/attachments/{$attachment->id}/download")
            ->assertRedirect();

        Storage::disk('public')->assertExists($attachment->disk_path);
    }

    public function test_a_secure_attachment_is_streamable_only_by_an_enrolled_user(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        [$course, $lesson] = $this->courseWithLesson($tenant);
        $enrolledUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $outsider = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/algebra/lessons/{$lesson->id}/attachments", [
            'title' => 'Lecture', 'type' => 'video', 'access_level' => 'secure',
            'file' => UploadedFile::fake()->create('lecture.mp4', 100, 'video/mp4'),
        ]);
        $attachment = $lesson->fresh()->attachments()->first();

        $this->actingAs($outsider)
            ->get("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/attachments/{$attachment->id}/stream")
            ->assertForbidden();

        $this->actingAs($enrolledUser)->post('/t/acme/lms/courses/algebra/enroll');
        $this->actingAs($enrolledUser)
            ->get("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/attachments/{$attachment->id}/stream")
            ->assertOk();
    }

    public function test_a_preview_lesson_allows_secure_attachment_streaming_without_enrollment(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true, 'pricing_policy' => 'paid', 'price' => 10, 'currency' => 'USD']);
        $lesson = $course->lessons()->create(['tenant_id' => $tenant->id, 'title' => 'Intro', 'content' => 'a', 'position' => 0, 'is_preview' => true]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/algebra/lessons/{$lesson->id}/attachments", [
            'title' => 'Lecture', 'type' => 'video', 'access_level' => 'secure',
            'file' => UploadedFile::fake()->create('lecture.mp4', 100, 'video/mp4'),
        ]);
        $attachment = $lesson->fresh()->attachments()->first();

        $this->actingAs($user)
            ->get("/t/acme/lms/courses/algebra/lessons/{$lesson->id}/attachments/{$attachment->id}/stream")
            ->assertOk();
    }

    public function test_secure_files_are_never_reachable_via_a_public_url(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        [$course, $lesson] = $this->courseWithLesson($tenant);

        $this->actingAs($owner)->post("/t/acme/lms/manage/courses/algebra/lessons/{$lesson->id}/attachments", [
            'title' => 'Lecture', 'type' => 'video', 'access_level' => 'secure',
            'file' => UploadedFile::fake()->create('lecture.mp4', 100, 'video/mp4'),
        ]);
        $attachment = $lesson->fresh()->attachments()->first();

        Storage::disk('public')->assertMissing($attachment->disk_path);
        Storage::disk('local')->assertExists($attachment->disk_path);
    }

    public function test_tenant_isolation_on_manage_attachment_upload(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner('acme');
        [$tenantB, $ownerB] = $this->tenantWithOwner('other');
        [$courseA, $lessonA] = $this->courseWithLesson($tenantA);

        $this->actingAs($ownerB)
            ->post("/t/other/lms/manage/courses/algebra/lessons/{$lessonA->id}/attachments", [
                'title' => 'Slides', 'type' => 'file', 'access_level' => 'open',
                'file' => UploadedFile::fake()->create('slides.pdf', 100, 'application/pdf'),
            ])
            ->assertNotFound();
    }
}
