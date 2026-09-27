<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\CourseAssigned;
use App\Notifications\PathAssigned;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\LearningPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningPathsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Learner']);
    }

    private function course(string $title): Course
    {
        $course = Course::create(['tenant_id' => $this->tenant->id, 'title' => $title, 'slug' => Str::slug($title), 'is_published' => true]);
        $course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => "{$title} lesson", 'content' => 'x', 'position' => 0]);

        return $course;
    }

    private function createPath(array $courseIds, bool $sequential = true): void
    {
        $this->actingAs($this->owner)->post('/t/acme/lms/manage/paths', [
            'title' => 'Onboarding', 'slug' => 'onboarding', 'description' => 'Start here.',
            'is_published' => 1, 'is_sequential' => $sequential ? 1 : 0,
            'course_ids' => $courseIds,
            'positions' => collect($courseIds)->mapWithKeys(fn ($id, $i) => [$id => $i + 1])->all(),
        ])->assertSessionHasNoErrors();
    }

    private function path(): LearningPath
    {
        return LearningPath::withoutGlobalScopes()->where('slug', 'onboarding')->firstOrFail();
    }

    private function completeCourse(Course $course): void
    {
        $lesson = $course->lessons()->withoutGlobalScopes()->first();
        $this->actingAs($this->learner)->post("/t/acme/lms/courses/{$course->slug}/lessons/{$lesson->id}/complete");
    }

    public function test_owners_build_an_ordered_path_that_links_the_courses(): void
    {
        [$a, $b, $c] = [$this->course('Alpha'), $this->course('Bravo'), $this->course('Charlie')];

        // Typed order wins over tick order.
        $this->actingAs($this->owner)->post('/t/acme/lms/manage/paths', [
            'title' => 'Onboarding', 'slug' => 'onboarding', 'is_published' => 1, 'is_sequential' => 1,
            'course_ids' => [$a->id, $b->id, $c->id],
            'positions' => [$a->id => 2, $b->id => 1, $c->id => 3],
        ])->assertSessionHasNoErrors();

        $this->assertSame([$b->id, $a->id, $c->id], $this->path()->courses()->withoutGlobalScopes()->pluck('courses.id')->all());
        $this->assertSame(
            [[$a->id, $b->id], [$c->id, $a->id]],
            DB::table('course_prerequisites')->orderBy('course_id')->get()->map(fn ($row) => [$row->course_id, $row->prerequisite_course_id])->all(),
        );

        // Turning ordering off removes only the links the path made.
        $this->actingAs($this->owner)->put('/t/acme/lms/manage/paths/onboarding', [
            'title' => 'Onboarding', 'slug' => 'onboarding', 'is_published' => 1,
            'course_ids' => [$a->id, $b->id, $c->id],
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('course_prerequisites')->count());
    }

    public function test_a_path_order_that_contradicts_course_prerequisites_is_refused(): void
    {
        [$a, $b] = [$this->course('Alpha'), $this->course('Bravo')];
        $a->prerequisites()->attach($b->id, ['tenant_id' => $this->tenant->id]); // Alpha needs Bravo first

        $this->actingAs($this->owner)->post('/t/acme/lms/manage/paths', [
            'title' => 'Onboarding', 'slug' => 'onboarding', 'is_sequential' => 1,
            'course_ids' => [$a->id, $b->id], 'positions' => [$a->id => 1, $b->id => 2],
        ])->assertSessionHasErrors('course_ids');

        $this->assertSame(0, LearningPath::withoutGlobalScopes()->count());
    }

    public function test_the_course_form_keeps_links_made_by_a_path(): void
    {
        [$a, $b] = [$this->course('Alpha'), $this->course('Bravo')];
        $this->createPath([$a->id, $b->id]);

        $this->actingAs($this->owner)->put('/t/acme/lms/manage/courses/bravo', ['title' => 'Bravo', 'slug' => 'bravo', 'is_published' => 1])
            ->assertSessionHasNoErrors();

        $this->assertTrue(DB::table('course_prerequisites')->where('course_id', $b->id)->where('prerequisite_course_id', $a->id)->exists());
        $this->actingAs($this->owner)->get('/t/acme/lms/manage/courses/bravo/edit')->assertSee('Also required by a learning path in order: Alpha');
    }

    public function test_learners_follow_the_path_step_by_step(): void
    {
        [$a, $b] = [$this->course('Alpha'), $this->course('Bravo')];
        $this->createPath([$a->id, $b->id]);

        $this->actingAs($this->learner)->get('/t/acme/lms/paths')->assertOk()->assertSee('Onboarding')->assertSee('taken in order');
        $this->actingAs($this->learner)->get('/t/acme/lms/paths/onboarding')
            ->assertOk()->assertSee('Start the path')->assertSeeInOrder(['Alpha', 'Not started', 'Bravo', 'Locked', 'Opens after you pass Alpha']);

        $this->actingAs($this->learner)->post('/t/acme/lms/paths/onboarding/start')
            ->assertRedirect(route('lms.courses.show', ['tenant' => 'acme', 'course' => 'alpha']));
        $this->assertTrue($a->isEnrolled($this->learner));

        $this->completeCourse($a);
        $this->actingAs($this->learner)->post('/t/acme/lms/paths/onboarding/start')
            ->assertRedirect(route('lms.courses.show', ['tenant' => 'acme', 'course' => 'bravo']));
        $this->completeCourse($b);

        $this->actingAs($this->learner)->get('/t/acme/lms/paths/onboarding')->assertSee('100%')->assertSee('Path complete');
        $this->actingAs($this->learner)->get('/t/acme/lms/courses/alpha')->assertSee('Part of the');
    }

    public function test_assigning_a_path_enrols_in_every_course_with_one_notification(): void
    {
        Notification::fake();
        [$a, $b] = [$this->course('Alpha'), $this->course('Bravo')];
        $this->createPath([$a->id, $b->id]);

        $this->actingAs($this->owner)->post('/t/acme/lms/manage/paths/onboarding/assign', [
            'who' => 'selected', 'user_ids' => [$this->learner->id], 'due_at' => now()->addMonth()->toDateString(),
        ])->assertRedirect(route('lms.manage.paths.edit', ['tenant' => 'acme', 'path' => 'onboarding']));

        $this->assertTrue($a->isEnrolled($this->learner));
        $this->assertTrue($b->isEnrolled($this->learner));
        Notification::assertSentTo($this->learner, PathAssigned::class);
        Notification::assertNotSentTo($this->learner, CourseAssigned::class);
        Notification::assertNotSentTo($this->owner, PathAssigned::class);

        $this->actingAs($this->owner)->get('/t/acme/lms/manage/paths/onboarding/edit')
            ->assertOk()->assertSee('Ada Learner')->assertSee('0 / 2 courses');
    }

    public function test_unpublished_paths_are_hidden_from_learners(): void
    {
        $this->createPath([$this->course('Alpha')->id]);
        $this->path()->update(['is_published' => false]);

        $this->actingAs($this->learner)->get('/t/acme/lms/paths')->assertDontSee('Onboarding');
        $this->actingAs($this->learner)->get('/t/acme/lms/paths/onboarding')->assertNotFound();
        $this->actingAs($this->learner)->get('/t/acme')->assertDontSee('lms/paths');
    }

    public function test_members_cannot_manage_paths(): void
    {
        $this->actingAs($this->learner)->get('/t/acme/lms/manage/paths')->assertForbidden();
    }
}
