<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseCertificate;
use Elibrary\Lms\Models\Enrollment;
use Elibrary\Lms\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSharingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $alpha;

    private Tenant $beta;

    private Tenant $gamma;

    private User $alphaOwner;

    private User $betaOwner;

    private User $betaLearner;

    private User $gammaLearner;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['alpha', 'beta', 'gamma'] as $slug) {
            $tenant = Tenant::create(['name' => ucfirst($slug).' Ltd', 'slug' => $slug, 'status' => TenantStatus::Active]);
            $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
            $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
            $this->{$slug} = $tenant;
        }

        $this->alphaOwner = User::factory()->create(['tenant_id' => $this->alpha->id, 'role' => UserRole::Owner]);
        $this->betaOwner = User::factory()->create(['tenant_id' => $this->beta->id, 'role' => UserRole::Owner]);
        $this->betaLearner = User::factory()->create(['tenant_id' => $this->beta->id, 'name' => 'Bola Beta']);
        $this->gammaLearner = User::factory()->create(['tenant_id' => $this->gamma->id]);

        $this->course = Course::withoutGlobalScopes()->create([
            'tenant_id' => $this->alpha->id, 'title' => 'Data Protection Basics', 'slug' => 'data-protection',
            'is_published' => true, 'pricing_policy' => 'paid', 'price' => 10000, 'currency' => 'NGN',
            'certificate_policy' => 'paid', 'certificate_price' => 2000, 'certificate_currency' => 'NGN',
        ]);
        $this->lesson = Lesson::withoutGlobalScopes()->create([
            'tenant_id' => $this->alpha->id, 'course_id' => $this->course->id, 'title' => 'Why it matters', 'content' => '<p>Secret sauce</p>', 'position' => 0,
        ]);
    }

    private function share(bool $public = true): void
    {
        $this->course->forceFill(['is_public' => $public])->save();
    }

    public function test_a_private_course_stays_invisible_to_other_workspaces(): void
    {
        $this->actingAs($this->betaLearner)->get('/t/beta/lms')->assertOk()->assertDontSee('Data Protection Basics');
        $this->actingAs($this->betaLearner)->get('/t/beta/lms/courses/data-protection')->assertNotFound();
        $this->actingAs($this->betaLearner)->get("/t/beta/lms/courses/data-protection/lessons/{$this->lesson->id}")->assertNotFound();
    }

    public function test_an_owner_opens_their_course_to_every_workspace(): void
    {
        $this->actingAs($this->alphaOwner)->put('/t/alpha/lms/manage/courses/data-protection', [
            'title' => 'Data Protection Basics', 'slug' => 'data-protection', 'is_published' => 1, 'is_public' => 1,
            'pricing_policy' => 'paid', 'price' => 10000, 'currency' => 'NGN',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($this->course->fresh()->is_public);

        $this->actingAs($this->betaLearner)->get('/t/beta/lms')->assertSee('Data Protection Basics');
        $this->actingAs($this->betaLearner)->get('/t/beta/lms/courses/data-protection')
            ->assertOk()->assertSee('Shared by Alpha Ltd')->assertSee('Free')->assertDontSee('NGN 10,000.00');
    }

    public function test_learners_elsewhere_enrol_free_and_their_progress_stays_in_their_workspace(): void
    {
        $this->share();

        $this->actingAs($this->betaLearner)->post('/t/beta/lms/courses/data-protection/enroll')
            ->assertRedirect(route('lms.courses.show', ['tenant' => 'beta', 'course' => 'data-protection']));
        $this->actingAs($this->betaLearner)->get("/t/beta/lms/courses/data-protection/lessons/{$this->lesson->id}")
            ->assertOk()->assertSee('Secret sauce');
        $this->actingAs($this->betaLearner)->post("/t/beta/lms/courses/data-protection/lessons/{$this->lesson->id}/complete");

        $enrollment = Enrollment::withoutGlobalScopes()->where('user_id', $this->betaLearner->id)->first();
        $this->assertSame($this->beta->id, $enrollment->tenant_id);
        // The paid certificate is free for a shared learner.
        $certificate = CourseCertificate::withoutGlobalScopes()->where('user_id', $this->betaLearner->id)->first();
        $this->assertNotNull($certificate);
        $this->assertSame($this->beta->id, $certificate->tenant_id);

        // The owning workspace's report only lists its own people.
        $this->actingAs($this->alphaOwner)->get('/t/alpha/lms/manage/courses/data-protection/report')
            ->assertOk()->assertDontSee('Bola Beta');
    }

    public function test_drafts_are_never_shared(): void
    {
        $this->share();
        $this->course->forceFill(['is_published' => false])->save();

        $this->actingAs($this->betaLearner)->get('/t/beta/lms/courses/data-protection')->assertNotFound();
    }

    public function test_other_workspaces_cannot_edit_a_shared_course(): void
    {
        $this->share();

        $this->actingAs($this->betaOwner)->get('/t/beta/lms/manage/courses/data-protection/edit')->assertNotFound();
        $this->actingAs($this->betaOwner)->put('/t/beta/lms/manage/courses/data-protection', ['title' => 'Hacked', 'slug' => 'data-protection'])->assertNotFound();
        $this->actingAs($this->betaOwner)->delete('/t/beta/lms/manage/courses/data-protection')->assertNotFound();
        $this->actingAs($this->betaOwner)->get("/t/beta/lms/manage/courses/data-protection/lessons/{$this->lesson->id}/edit")->assertNotFound();
        $this->assertSame('Data Protection Basics', $this->course->fresh()->title);
    }

    public function test_owners_elsewhere_can_assign_a_shared_course_and_follow_progress(): void
    {
        $this->share();

        $this->actingAs($this->betaOwner)->get('/t/beta/lms/manage/courses')
            ->assertOk()->assertSee('Shared with your workspace')->assertSee('From Alpha Ltd');
        $this->actingAs($this->betaOwner)->get('/t/beta/lms/manage/courses/data-protection/report')
            ->assertOk()->assertDontSee('Edit course');
        $this->actingAs($this->betaOwner)->post('/t/beta/lms/manage/courses/data-protection/assignments', [
            'who' => 'selected', 'user_ids' => [$this->betaLearner->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->beta->id, Enrollment::withoutGlobalScopes()->where('user_id', $this->betaLearner->id)->value('tenant_id'));
        $this->actingAs($this->betaOwner)->get('/t/beta/lms/manage/courses/data-protection/report')->assertSee('Bola Beta');
    }

    public function test_a_workspaces_own_course_wins_a_slug_clash(): void
    {
        $this->share();
        Course::withoutGlobalScopes()->create(['tenant_id' => $this->beta->id, 'title' => 'Beta Own Course', 'slug' => 'data-protection', 'is_published' => true]);

        $this->actingAs($this->betaLearner)->get('/t/beta/lms/courses/data-protection')->assertOk()->assertSee('Beta Own Course');
    }

    public function test_platform_admins_share_with_chosen_workspaces(): void
    {
        $admin = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)->get('/admin/shared-courses')->assertOk()->assertSee('Data Protection Basics')->assertSee('Private');
        $this->actingAs($admin)->put("/admin/shared-courses/{$this->course->id}", ['workspace_ids' => [$this->gamma->id]])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->gammaLearner)->get('/t/gamma/lms/courses/data-protection')->assertOk();
        $this->actingAs($this->betaLearner)->get('/t/beta/lms/courses/data-protection')->assertNotFound();

        // A workspace owner signed in to their workspace can't reach the admin page:
        // with no workspace in the URL their account doesn't resolve at all.
        auth()->logout();
        $this->post('/t/alpha/login', ['email' => $this->alphaOwner->email, 'password' => 'password'])
            ->assertRedirect(route('tenant.home', ['tenant' => 'alpha']));
        $this->app['auth']->forgetGuards(); // a fresh request re-reads the session, as in production
        $this->get('/admin/shared-courses')->assertRedirect(route('login'));
    }

    public function test_a_shared_courses_final_exam_can_be_taken_and_stays_out_of_the_exam_list(): void
    {
        $exam = Exam::withoutGlobalScopes()->create([
            'tenant_id' => $this->alpha->id, 'title' => 'Protection Final', 'slug' => 'protection-final',
            'duration_minutes' => 10, 'pass_percentage' => 50, 'is_published' => true,
        ]);
        $question = $exam->questions()->withoutGlobalScopes()->create(['tenant_id' => $this->alpha->id, 'question_text' => 'Q', 'position' => 0]);
        $right = $question->options()->withoutGlobalScopes()->create(['tenant_id' => $this->alpha->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $this->course->forceFill(['assessment_mode' => 'course_final', 'final_exam_id' => $exam->id, 'is_public' => true])->save();

        $this->actingAs($this->betaLearner)->get('/t/beta/cbt')->assertOk()->assertDontSee('Protection Final');
        $this->actingAs($this->betaOwner)->get('/t/beta/cbt/manage/exams')->assertOk()->assertDontSee('Protection Final');
        $this->actingAs($this->betaOwner)->get('/t/beta/cbt/manage/exams/protection-final/edit')->assertNotFound();

        $this->actingAs($this->betaLearner)->post('/t/beta/lms/courses/data-protection/enroll');
        $this->actingAs($this->betaLearner)->post('/t/beta/cbt/exams/protection-final/start');
        $attempt = ExamAttempt::withoutGlobalScopes()->where('user_id', $this->betaLearner->id)->firstOrFail();
        $this->assertSame($this->beta->id, $attempt->tenant_id);

        $this->actingAs($this->betaLearner)->post("/t/beta/cbt/attempts/{$attempt->id}", ['answers' => [$question->id => [$right->id]]]);
        $this->assertEquals(100, $attempt->fresh()->score);
    }

    public function test_an_owner_can_make_their_course_private_again(): void
    {
        $this->share();

        $this->actingAs($this->alphaOwner)->put('/t/alpha/lms/manage/courses/data-protection', [
            'title' => 'Data Protection Basics', 'slug' => 'data-protection', 'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->betaLearner)->get('/t/beta/lms/courses/data-protection')->assertNotFound();
    }
}
