<?php

namespace Tests\Feature;

use App\Enums\Badge;
use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\LearningDay;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserBadge;
use App\Notifications\AttemptNeedsMarking;
use App\Notifications\CourseAssigned;
use App\Support\Achievements;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsAndAchievementsTest extends TestCase
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
        $this->tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Learner']);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
    }

    private function course(): Course
    {
        $course = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Basics', 'slug' => 'basics', 'is_published' => true]);
        $course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'Only lesson', 'content' => 'x', 'position' => 0]);

        return $course;
    }

    private function exam(string $type = 'single'): Exam
    {
        $exam = Exam::create([
            'tenant_id' => $this->tenant->id, 'title' => 'Quiz', 'slug' => 'quiz',
            'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->create(['tenant_id' => $this->tenant->id, 'question_text' => 'Q', 'position' => 0, 'answer_type' => $type]);
        if ($type === 'single') {
            $question->options()->create(['tenant_id' => $this->tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        }

        return $exam;
    }

    private function takeExam(Exam $exam, mixed $answer): void
    {
        $this->actingAs($this->learner)->post('/t/acme/cbt/exams/quiz/start');
        $attempt = $exam->attempts()->withoutGlobalScopes()->where('user_id', $this->learner->id)->latest('id')->first();
        $question = $exam->questions()->withoutGlobalScopes()->first();
        $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [$question->id => $answer]]);
    }

    public function test_assignments_land_under_the_bell_and_open_their_link(): void
    {
        $course = $this->course();
        $assignment = CourseAssignment::create(['tenant_id' => $this->tenant->id, 'course_id' => $course->id, 'user_id' => $this->learner->id, 'assigned_by_user_id' => $this->owner->id, 'due_at' => now()->addWeek()]);
        $this->learner->notify(new CourseAssigned($assignment->setRelation('course', $course), $this->tenant, 'Olu Owner'));

        $this->actingAs($this->learner)->get('/t/acme')
            ->assertOk()->assertSee('Notifications (1 unread)', false)->assertSee('New course assigned')->assertSee('Olu Owner assigned you Basics');

        $notification = $this->learner->notifications()->first();
        $this->actingAs($this->learner)->post("/t/acme/notifications/{$notification->id}/open")
            ->assertRedirect(route('lms.courses.show', ['tenant' => 'acme', 'course' => 'basics']));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_people_cannot_open_someone_elses_notification(): void
    {
        $course = $this->course();
        $this->learner->notify(new CourseAssigned(
            CourseAssignment::create(['tenant_id' => $this->tenant->id, 'course_id' => $course->id, 'user_id' => $this->learner->id, 'assigned_by_user_id' => $this->owner->id])->setRelation('course', $course),
            $this->tenant, 'Olu Owner',
        ));
        $notification = $this->learner->notifications()->first();

        $this->actingAs($this->owner)->post("/t/acme/notifications/{$notification->id}/open")->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_and_the_notifications_page(): void
    {
        $course = $this->course();
        $assignment = CourseAssignment::create(['tenant_id' => $this->tenant->id, 'course_id' => $course->id, 'user_id' => $this->learner->id, 'assigned_by_user_id' => $this->owner->id])->setRelation('course', $course);
        $this->learner->notify(new CourseAssigned($assignment, $this->tenant, 'Olu Owner'));
        $this->learner->notify(new CourseAssigned($assignment, $this->tenant, 'Bola Owner'));

        $this->actingAs($this->learner)->get('/t/acme/notifications')->assertOk()->assertSee('2 unread');
        $this->actingAs($this->learner)->post('/t/acme/notifications/read-all');
        $this->assertSame(0, $this->learner->unreadNotifications()->count());
    }

    public function test_owners_are_told_when_an_essay_needs_marking(): void
    {
        $exam = $this->exam('essay');
        $this->takeExam($exam, 'My thoughtful answer.');

        $this->assertSame(1, $this->owner->notifications()->where('type', AttemptNeedsMarking::class)->count());
        $this->assertSame(0, $this->learner->notifications()->where('type', AttemptNeedsMarking::class)->count());
    }

    public function test_completing_a_lesson_starts_a_streak_and_earns_badges(): void
    {
        $course = $this->course();
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');
        $lesson = $course->lessons()->withoutGlobalScopes()->first();

        $this->actingAs($this->learner)->post("/t/acme/lms/courses/basics/lessons/{$lesson->id}/complete");

        $this->assertSame(1, Achievements::streak($this->learner)['current']);
        $badges = UserBadge::withoutGlobalScopes()->where('user_id', $this->learner->id)->pluck('badge')->map->value->sort()->values()->all();
        $this->assertSame([Badge::FirstCourse->value, Badge::FirstLesson->value], $badges);
        $this->assertSame(2, $this->learner->notifications()->count());

        $this->actingAs($this->learner)->get('/t/acme')
            ->assertSee('1 day')->assertSee('2 of 7 earned')->assertSee('Course finisher');
    }

    public function test_passing_with_full_marks_earns_the_exam_badges(): void
    {
        $exam = $this->exam();
        $option = $exam->questions()->withoutGlobalScopes()->first()->options()->withoutGlobalScopes()->first();

        $this->takeExam($exam, $option->id);

        $badges = UserBadge::withoutGlobalScopes()->where('user_id', $this->learner->id)->pluck('badge')->map->value->all();
        $this->assertContains(Badge::FirstPass->value, $badges);
        $this->assertContains(Badge::PerfectScore->value, $badges);
    }

    public function test_streaks_count_consecutive_days_and_award_the_week_badge(): void
    {
        foreach ([0, 1, 2, 3, 4, 5, 6, 9, 10] as $ago) {
            LearningDay::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->learner->id, 'day' => today()->subDays($ago)]);
        }

        $streak = Achievements::streak($this->learner);
        $this->assertSame(7, $streak['current']);
        $this->assertSame(7, $streak['best']);
        $this->assertTrue($streak['today']);

        Achievements::evaluate($this->learner);
        $this->assertTrue(UserBadge::withoutGlobalScopes()->where('user_id', $this->learner->id)->where('badge', Badge::Streak7)->exists());
    }

    public function test_a_streak_survives_until_the_end_of_today(): void
    {
        foreach ([1, 2] as $ago) {
            LearningDay::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->learner->id, 'day' => today()->subDays($ago)]);
        }

        $this->assertSame(2, Achievements::streak($this->learner)['current']);
        $this->assertFalse(Achievements::streak($this->learner)['today']);
    }

    public function test_owners_see_a_members_achievements(): void
    {
        UserBadge::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->learner->id, 'badge' => Badge::FirstPass, 'earned_at' => now()]);

        $this->actingAs($this->owner)->get("/t/acme/team/{$this->learner->id}")
            ->assertOk()->assertSee('Current learning streak', false)->assertSee('1 of 7 earned');
    }
}
