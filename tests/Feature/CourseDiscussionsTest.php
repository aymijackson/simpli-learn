<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\DiscussionActivity;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseDiscussion;
use Elibrary\Lms\Models\CourseDiscussionReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CourseDiscussionsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $teacher;

    private User $ada;

    private User $bola;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->teacher = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Tola Teacher']);
        $this->ada = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Asker']);
        $this->bola = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Bola Helper']);

        $this->course = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Security', 'slug' => 'security', 'is_published' => true]);
        $this->course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'Passwords', 'content' => 'x', 'position' => 0]);
        $this->course->instructors()->attach($this->teacher->id, ['tenant_id' => $this->tenant->id]);
        foreach ([$this->ada, $this->bola] as $learner) {
            $this->course->enrollments()->create(['tenant_id' => $this->tenant->id, 'user_id' => $learner->id, 'enrolled_at' => now()]);
        }
    }

    private function ask(): CourseDiscussion
    {
        $this->actingAs($this->ada)->post('/t/acme/lms/courses/security/discussions', [
            'title' => 'How long should a password be?', 'body' => 'The lesson says long, but how long exactly?',
        ])->assertSessionHasNoErrors();

        return CourseDiscussion::withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    public function test_learners_ask_and_the_instructor_is_told(): void
    {
        Notification::fake();
        $discussion = $this->ask();

        Notification::assertSentTo($this->teacher, DiscussionActivity::class, fn ($n) => $n->kind === 'new');
        Notification::assertNotSentTo($this->ada, DiscussionActivity::class);

        $this->actingAs($this->bola)->get('/t/acme/lms/courses/security/discussions')
            ->assertOk()->assertSee('How long should a password be?')->assertSee('Ada Asker');
        $this->actingAs($this->bola)->get("/t/acme/lms/courses/security/discussions/{$discussion->id}")
            ->assertOk()->assertSee('The lesson says long, but how long exactly?');
    }

    public function test_replies_notify_the_thread_and_instructor_replies_are_marked(): void
    {
        Notification::fake();
        $discussion = $this->ask();

        $this->actingAs($this->bola)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies", ['body' => 'I use four random words.']);
        Notification::assertSentTo($this->ada, DiscussionActivity::class, fn ($n) => $n->kind === 'reply');
        $this->assertNull($discussion->fresh()->staff_replied_at);

        $this->actingAs($this->teacher)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies", ['body' => 'At least 14 characters; a passphrase is easiest.']);
        $this->assertNotNull($discussion->fresh()->staff_replied_at);
        $this->assertSame(2, $discussion->fresh()->replies_count);
        Notification::assertSentTo($this->bola, DiscussionActivity::class, fn ($n) => $n->kind === 'reply');

        $this->actingAs($this->ada)->get("/t/acme/lms/courses/security/discussions/{$discussion->id}")
            ->assertSeeInOrder(['I use four random words.', 'Tola Teacher', 'Instructor', 'At least 14 characters']);
    }

    public function test_the_asker_marks_the_answer_but_others_cannot(): void
    {
        $discussion = $this->ask();
        $this->actingAs($this->teacher)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies", ['body' => 'Fourteen or more.']);
        $reply = CourseDiscussionReply::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->bola)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/answer/{$reply->id}")->assertForbidden();
        $this->actingAs($this->ada)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/answer/{$reply->id}");
        $this->assertSame($reply->id, $discussion->fresh()->answer_reply_id);

        $this->actingAs($this->ada)->get('/t/acme/lms/courses/security/discussions?filter=unanswered')->assertSee('No discussions yet');
    }

    public function test_staff_moderate_and_locked_threads_refuse_learner_replies(): void
    {
        $discussion = $this->ask();

        $this->actingAs($this->ada)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/moderate", ['action' => 'lock'])->assertForbidden();
        $this->actingAs($this->teacher)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/moderate", ['action' => 'lock']);
        $this->actingAs($this->owner)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/moderate", ['action' => 'pin']);
        $discussion->refresh();
        $this->assertTrue($discussion->is_locked);
        $this->assertTrue($discussion->is_pinned);

        $this->actingAs($this->bola)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies", ['body' => 'Too late?'])->assertForbidden();
        $this->actingAs($this->teacher)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies", ['body' => 'Closing note.'])->assertRedirect();
    }

    public function test_people_outside_the_course_cannot_read_or_post(): void
    {
        $discussion = $this->ask();
        $outsider = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($outsider)->get('/t/acme/lms/courses/security/discussions')->assertForbidden();
        $this->actingAs($outsider)->get("/t/acme/lms/courses/security/discussions/{$discussion->id}")->assertForbidden();
        $this->actingAs($outsider)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies", ['body' => 'Hi'])->assertForbidden();
    }

    public function test_authors_withdraw_unanswered_questions_and_delete_their_own_replies(): void
    {
        $discussion = $this->ask();
        $this->actingAs($this->bola)->delete("/t/acme/lms/courses/security/discussions/{$discussion->id}")->assertForbidden();

        $this->actingAs($this->bola)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies", ['body' => 'Mine to delete']);
        $reply = CourseDiscussionReply::withoutGlobalScopes()->firstOrFail();
        // Once someone has replied, only staff can remove the whole thread.
        $this->actingAs($this->ada)->delete("/t/acme/lms/courses/security/discussions/{$discussion->id}")->assertForbidden();
        $this->actingAs($this->ada)->delete("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies/{$reply->id}")->assertForbidden();
        $this->actingAs($this->bola)->delete("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies/{$reply->id}")->assertRedirect();
        $this->assertSame(0, $discussion->fresh()->replies_count);

        $this->actingAs($this->ada)->delete("/t/acme/lms/courses/security/discussions/{$discussion->id}")->assertRedirect();
        $this->assertNull(CourseDiscussion::withoutGlobalScopes()->find($discussion->id));
    }

    public function test_staff_see_questions_waiting_for_a_reply(): void
    {
        $discussion = $this->ask();

        $this->actingAs($this->teacher)->get('/t/acme/lms/manage/discussions')
            ->assertOk()->assertSee('How long should a password be?')->assertSee('Needs a reply');
        $this->actingAs($this->teacher)->post("/t/acme/lms/courses/security/discussions/{$discussion->id}/replies", ['body' => 'Answered.']);
        $this->actingAs($this->teacher)->get('/t/acme/lms/manage/discussions')->assertSee('All caught up');
        $this->actingAs($this->ada)->get('/t/acme/lms/manage/discussions')->assertForbidden();
    }

    public function test_questions_can_be_about_a_lesson(): void
    {
        $lesson = $this->course->lessons()->withoutGlobalScopes()->first();

        $this->actingAs($this->ada)->get("/t/acme/lms/courses/security/lessons/{$lesson->id}")->assertSee('Discuss it');
        $this->actingAs($this->ada)->post('/t/acme/lms/courses/security/discussions', [
            'title' => 'Question on passwords', 'body' => 'Is a password manager safe?', 'lesson_id' => $lesson->id,
        ]);

        $this->actingAs($this->ada)->get("/t/acme/lms/courses/security/discussions?lesson={$lesson->id}")
            ->assertSee('About the lesson', false)->assertSee('Question on passwords');
    }
}
