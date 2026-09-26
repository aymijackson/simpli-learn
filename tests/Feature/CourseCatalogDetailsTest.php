<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Enums\CourseLevel;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseReview;
use Elibrary\Lms\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseCatalogDetailsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
    }

    private function course(array $attributes = []): Course
    {
        return Course::create(['tenant_id' => $this->tenant->id, 'is_published' => true] + $attributes + ['title' => 'Course', 'slug' => str()->slug($attributes['title'] ?? 'course')]);
    }

    private function learner(?Course $enrolledIn = null): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        if ($enrolledIn) {
            Enrollment::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'course_id' => $enrolledIn->id, 'user_id' => $user->id]);
        }

        return $user;
    }

    public function test_an_owner_can_save_catalog_details_and_a_cover_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner)->post('/t/acme/lms/manage/courses', [
            'title' => 'Data Security', 'slug' => 'data-security', 'is_published' => 1,
            'subtitle' => 'Protect fans, artists and the business',
            'category' => '  Compliance ', 'level' => 'beginner', 'duration_hours' => '1.5',
            'instructor_name' => 'Jackson', 'instructor_bio' => 'Head of security',
            'outcomes_text' => "- Explain the NDPA\n\n* Spot phishing\nReport incidents",
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 1280, 720),
        ])->assertRedirect('/t/acme/lms/manage/courses/data-security/edit');

        $course = Course::withoutGlobalScopes()->where('slug', 'data-security')->first();
        $this->assertSame('Compliance', $course->category);
        $this->assertSame(CourseLevel::Beginner, $course->level);
        $this->assertSame(90, $course->duration_minutes);
        $this->assertSame('1.5 hours', $course->durationLabel());
        $this->assertSame(['Explain the NDPA', 'Spot phishing', 'Report incidents'], $course->outcomes);
        Storage::disk('public')->assertExists($course->cover_image_path);

        // Removing the cover deletes the file.
        $path = $course->cover_image_path;
        $this->actingAs($this->owner)->put('/t/acme/lms/manage/courses/data-security', ['title' => 'Data Security', 'slug' => 'data-security', 'remove_cover' => 1]);
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($course->fresh()->cover_image_path);
    }

    public function test_the_catalog_filters_by_category_and_level_and_sorts_by_rating(): void
    {
        $security = $this->course(['title' => 'Data Security', 'category' => 'Compliance', 'level' => 'beginner']);
        $leadership = $this->course(['title' => 'Leading Teams', 'category' => 'Leadership', 'level' => 'advanced']);
        foreach ([5, 5] as $stars) {
            CourseReview::create(['tenant_id' => $this->tenant->id, 'course_id' => $leadership->id, 'user_id' => $this->learner()->id, 'stars' => $stars]);
        }
        CourseReview::create(['tenant_id' => $this->tenant->id, 'course_id' => $security->id, 'user_id' => $this->learner()->id, 'stars' => 3]);

        $this->actingAs($this->owner)->get('/t/acme/lms?category=Compliance')->assertSee('Data Security')->assertDontSee('Leading Teams');
        $this->actingAs($this->owner)->get('/t/acme/lms?level=advanced')->assertSee('Leading Teams')->assertDontSee('Data Security');
        $this->actingAs($this->owner)->get('/t/acme/lms?sort=rating')->assertSeeInOrder(['Leading Teams', 'Data Security']);
        $this->actingAs($this->owner)->get('/t/acme/lms')->assertSee('Compliance')->assertSee('Leadership')->assertSee('5.0');
    }

    public function test_the_course_page_shows_outcomes_instructor_and_reviews(): void
    {
        $course = $this->course([
            'title' => 'Data Security', 'subtitle' => 'Protect everyone', 'instructor_name' => 'Jackson Dkm',
            'instructor_bio' => 'Head of security', 'outcomes' => ['Spot phishing', 'Report incidents'],
        ]);
        $reviewer = $this->learner($course);
        $reviewer->update(['name' => 'Ada Obi']);
        CourseReview::create(['tenant_id' => $this->tenant->id, 'course_id' => $course->id, 'user_id' => $reviewer->id, 'stars' => 4, 'comment' => 'Really practical']);

        $this->actingAs($this->learner())->get('/t/acme/lms/courses/data-security')
            ->assertOk()
            ->assertSee('Protect everyone')
            ->assertSee("What you'll learn", false)->assertSee('Spot phishing')
            ->assertSee('Your instructor')->assertSee('Head of security')
            ->assertSee('4.0')->assertSee('Ada Obi')->assertSee('Really practical')
            ->assertDontSee('Rate this course'); // not enrolled
    }

    public function test_enrolled_learners_can_review_once_and_update_it(): void
    {
        $course = $this->course(['title' => 'Data Security']);
        $learner = $this->learner($course);

        $this->actingAs($learner)->get('/t/acme/lms/courses/data-security')->assertSee('Rate this course');
        $this->actingAs($learner)->post('/t/acme/lms/courses/data-security/reviews', ['stars' => 4, 'comment' => 'Good'])
            ->assertRedirect('/t/acme/lms/courses/data-security#reviews');
        $this->actingAs($learner)->post('/t/acme/lms/courses/data-security/reviews', ['stars' => 5]);

        $this->assertSame(1, CourseReview::withoutGlobalScopes()->count());
        $this->assertSame(5, CourseReview::withoutGlobalScopes()->first()->stars);
    }

    public function test_learners_who_are_not_enrolled_cannot_review(): void
    {
        $this->course(['title' => 'Data Security']);

        $this->actingAs($this->learner())->post('/t/acme/lms/courses/data-security/reviews', ['stars' => 1])->assertForbidden();
        $this->assertSame(0, CourseReview::withoutGlobalScopes()->count());
    }

    public function test_owners_can_remove_reviews_but_other_learners_cannot(): void
    {
        $course = $this->course(['title' => 'Data Security']);
        $author = $this->learner($course);
        $review = CourseReview::create(['tenant_id' => $this->tenant->id, 'course_id' => $course->id, 'user_id' => $author->id, 'stars' => 1, 'comment' => 'Spam']);

        $this->actingAs($this->learner($course))->delete("/t/acme/lms/courses/data-security/reviews/{$review->id}")->assertForbidden();

        $this->actingAs($this->owner)->delete("/t/acme/lms/courses/data-security/reviews/{$review->id}")->assertRedirect();
        $this->assertSame(0, CourseReview::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'courses.review_removed']);
    }
}
