<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseOrderingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $learner;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
    }

    private function course(string $title, array $attributes = [], int $lessons = 2): Course
    {
        $course = Course::create($attributes + [
            'tenant_id' => $this->tenant->id, 'title' => $title, 'slug' => Str::slug($title), 'is_published' => true,
        ]);
        foreach (range(1, $lessons) as $i) {
            $course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => "{$title} lesson {$i}", 'content' => 'x', 'position' => $i]);
        }

        return $course;
    }

    private function makePrerequisite(Course $course, Course $prerequisite): void
    {
        $course->prerequisites()->attach($prerequisite->id, ['tenant_id' => $this->tenant->id]);
    }

    /** No tenant is current outside a request, so read lessons without the tenant scope. */
    private function lessonsOf(Course $course)
    {
        return $course->lessons()->withoutGlobalScopes()->orderBy('position')->get();
    }

    private function lessonUrl(Course $course, Lesson $lesson): string
    {
        return "/t/acme/lms/courses/{$course->slug}/lessons/{$lesson->id}";
    }

    private function completeAll(Course $course): void
    {
        foreach ($this->lessonsOf($course) as $lesson) {
            $this->actingAs($this->learner)->post($this->lessonUrl($course, $lesson).'/complete');
        }
    }

    public function test_sequential_lessons_open_one_after_another(): void
    {
        $course = $this->course('Basics', ['sequential_lessons' => true]);
        [$first, $second] = $this->lessonsOf($course)->all();
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');

        $this->actingAs($this->learner)->get($this->lessonUrl($course, $second))->assertForbidden();
        $this->actingAs($this->learner)->get('/t/acme/lms/courses/basics')->assertSee('Lessons unlock in order');

        $this->actingAs($this->learner)->post($this->lessonUrl($course, $first).'/complete');
        $this->actingAs($this->learner)->get($this->lessonUrl($course, $second))->assertOk();
    }

    public function test_without_the_setting_lessons_open_freely(): void
    {
        $course = $this->course('Basics');
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');

        $this->actingAs($this->learner)->get($this->lessonUrl($course, $this->lessonsOf($course)->last()))->assertOk();
    }

    public function test_preview_lessons_stay_open_to_visitors_in_a_sequential_course(): void
    {
        $course = $this->course('Basics', ['sequential_lessons' => true]);
        $this->lessonsOf($course)->last()->update(['is_preview' => true]);

        $this->actingAs($this->learner)->get($this->lessonUrl($course, $this->lessonsOf($course)->last()))->assertOk();
    }

    public function test_a_course_stays_closed_until_its_prerequisite_is_passed(): void
    {
        $part1 = $this->course('Part One');
        $part2 = $this->course('Part Two');
        $this->makePrerequisite($part2, $part1);

        $this->actingAs($this->learner)->get('/t/acme/lms/courses/part-two')
            ->assertOk()->assertSee('Complete this course first')->assertSee('Opens after you pass')->assertDontSee('>Enroll<', false);

        $this->actingAs($this->learner)->post('/t/acme/lms/courses/part-two/enroll')
            ->assertRedirect(route('lms.courses.show', ['tenant' => 'acme', 'course' => 'part-two']))
            ->assertSessionHas('error');
        $this->assertFalse($part2->isEnrolled($this->learner));

        $this->actingAs($this->learner)->post('/t/acme/lms/courses/part-one/enroll');
        $this->completeAll($part1);

        $this->actingAs($this->learner)->post('/t/acme/lms/courses/part-two/enroll');
        $this->assertTrue($part2->isEnrolled($this->learner));
        $this->actingAs($this->learner)->get($this->lessonUrl($part2, $this->lessonsOf($part2)->last()))->assertOk();
    }

    public function test_a_paid_course_cannot_be_bought_before_its_prerequisite(): void
    {
        $part1 = $this->course('Part One');
        $part2 = $this->course('Part Two', ['pricing_policy' => 'paid', 'price' => 5000, 'currency' => 'NGN']);
        $this->makePrerequisite($part2, $part1);

        $this->actingAs($this->learner)->get('/t/acme/lms/courses/part-two/purchase')
            ->assertRedirect(route('lms.courses.show', ['tenant' => 'acme', 'course' => 'part-two']));
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/part-two/purchase', ['gateway' => 'bank_transfer'])
            ->assertRedirect(route('lms.courses.show', ['tenant' => 'acme', 'course' => 'part-two']));
    }

    public function test_an_assigned_course_keeps_its_lessons_locked_until_the_prerequisite_is_passed(): void
    {
        $part1 = $this->course('Part One');
        $part2 = $this->course('Part Two');
        $this->makePrerequisite($part2, $part1);
        $part2->enrollments()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->learner->id, 'enrolled_at' => now()]);

        $this->actingAs($this->learner)->get($this->lessonUrl($part2, $this->lessonsOf($part2)->first()))->assertForbidden();
        $this->actingAs($this->learner)->get('/t/acme/lms/courses/part-two')->assertSee('Complete the required course first');
    }

    public function test_owners_set_order_on_the_course_form_and_loops_are_refused(): void
    {
        $part1 = $this->course('Part One');
        $part2 = $this->course('Part Two');

        $this->actingAs($this->owner)->get('/t/acme/lms/manage/courses/part-two/edit')
            ->assertOk()->assertSee('Lessons must be completed in order')->assertSee('Part One');

        $this->actingAs($this->owner)->put('/t/acme/lms/manage/courses/part-two', [
            'title' => 'Part Two', 'slug' => 'part-two', 'is_published' => 1,
            'sequential_lessons' => 1, 'prerequisite_ids' => [$part1->id],
        ])->assertSessionHasNoErrors();

        $part2->refresh();
        $this->assertTrue($part2->sequential_lessons);
        $this->assertSame([$part1->id], $part2->prerequisites->pluck('id')->all());

        // Part One can't now also require Part Two.
        $this->actingAs($this->owner)->put('/t/acme/lms/manage/courses/part-one', [
            'title' => 'Part One', 'slug' => 'part-one', 'prerequisite_ids' => [$part2->id],
        ])->assertSessionHasErrors('prerequisite_ids');

        // A course can't require itself.
        $this->actingAs($this->owner)->put('/t/acme/lms/manage/courses/part-one', [
            'title' => 'Part One', 'slug' => 'part-one', 'prerequisite_ids' => [$part1->id],
        ])->assertSessionHasErrors('prerequisite_ids.0');
    }
}
