<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\CourseAssigned;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Lms\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $ada;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner, 'name' => 'Olu Owner']);
        $this->ada = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Lovelace', 'email' => 'ada@acme.com']);
        $this->course = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Security', 'slug' => 'security', 'is_published' => true]);
        $this->course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'One', 'content' => 'x', 'position' => 0]);
    }

    private function key(bool $write = false): string
    {
        return ApiToken::issue($this->tenant->id, $this->owner, 'Test key', $write)[1];
    }

    private function api(string $key)
    {
        return $this->withHeader('Authorization', 'Bearer '.$key);
    }

    public function test_requests_need_a_valid_active_key(): void
    {
        $this->getJson('/api/v1/courses')->assertUnauthorized()->assertJson(['message' => 'Missing or invalid API key.']);
        $this->api('elib_not-a-real-key')->getJson('/api/v1/courses')->assertUnauthorized();

        $key = $this->key();
        ApiToken::withoutGlobalScopes()->first()->forceFill(['revoked_at' => now()])->save();
        $this->api($key)->getJson('/api/v1/courses')->assertUnauthorized();
    }

    public function test_read_endpoints_return_the_workspaces_data(): void
    {
        $key = $this->key();

        $this->api($key)->getJson('/api/v1/people')->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['email' => 'ada@acme.com', 'role' => 'member', 'active' => true]);
        $this->api($key)->getJson('/api/v1/people?email=ADA@acme.com')->assertJsonCount(1, 'data');

        $this->api($key)->getJson('/api/v1/courses')->assertOk()
            ->assertJsonPath('data.0.title', 'Security')->assertJsonPath('data.0.lessons', 1);

        $this->course->enrollments()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->ada->id, 'enrolled_at' => now()]);
        $this->api($key)->getJson("/api/v1/courses/{$this->course->id}/progress")->assertOk()
            ->assertJsonFragment(['email' => 'ada@acme.com']);

        $exam = Exam::create(['tenant_id' => $this->tenant->id, 'title' => 'Quiz', 'slug' => 'quiz', 'duration_minutes' => 5, 'pass_percentage' => 50, 'is_published' => true]);
        ExamAttempt::create(['tenant_id' => $this->tenant->id, 'exam_id' => $exam->id, 'user_id' => $this->ada->id, 'started_at' => now()->subHour(), 'submitted_at' => now()->subMinutes(50), 'score' => 80]);
        $this->api($key)->getJson('/api/v1/exams')->assertJsonPath('data.0.pass_percentage', 50);
        $this->api($key)->getJson("/api/v1/exams/{$exam->id}/results")->assertJsonPath('data.0.score', 80)->assertJsonPath('data.0.passed', true);
        $this->api($key)->getJson("/api/v1/exams/{$exam->id}/results?since=".now()->toDateString().'T23:59:59')->assertJsonCount(0, 'data');

        $this->api($key)->getJson('/api/v1/certificates')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_key_never_sees_another_workspace(): void
    {
        $other = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        $otherCourse = Course::withoutGlobalScopes()->create(['tenant_id' => $other->id, 'title' => 'Secret', 'slug' => 'secret', 'is_published' => true, 'is_public' => true]);
        User::factory()->create(['tenant_id' => $other->id, 'email' => 'stranger@other.com']);
        $key = $this->key();

        $this->api($key)->getJson('/api/v1/courses')->assertJsonMissing(['title' => 'Secret']);
        $this->api($key)->getJson('/api/v1/people')->assertJsonMissing(['email' => 'stranger@other.com']);
        $this->api($key)->getJson("/api/v1/courses/{$otherCourse->id}/progress")->assertNotFound();
    }

    public function test_read_only_keys_cannot_write(): void
    {
        $key = $this->key();

        $this->api($key)->postJson('/api/v1/people', ['name' => 'New', 'email' => 'new@acme.com'])->assertForbidden();
        $this->api($key)->postJson("/api/v1/courses/{$this->course->id}/assignments", ['emails' => ['ada@acme.com']])->assertForbidden();
    }

    public function test_write_keys_add_people_and_assign_courses(): void
    {
        Notification::fake();
        $key = $this->key(write: true);

        $this->api($key)->postJson('/api/v1/people', ['name' => 'Bola New', 'email' => 'Bola@Acme.com'])
            ->assertCreated()->assertJsonPath('data.email', 'bola@acme.com')->assertJsonPath('data.role', 'member');
        $this->api($key)->postJson('/api/v1/people', ['name' => 'Again', 'email' => 'bola@acme.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        // Owners can't be created through the API.
        $this->api($key)->postJson('/api/v1/people', ['name' => 'Boss', 'email' => 'boss@acme.com', 'role' => 'owner'])->assertJsonValidationErrors('role');

        $this->api($key)->postJson("/api/v1/courses/{$this->course->id}/assignments", ['emails' => ['ada@acme.com', 'ghost@acme.com'], 'due_at' => now()->addWeek()->toDateString()])
            ->assertOk()->assertJsonPath('data.assigned', ['ada@acme.com'])->assertJsonPath('data.not_found', ['ghost@acme.com']);
        $this->assertTrue($this->course->isEnrolled($this->ada));
        Notification::assertSentTo($this->ada, CourseAssigned::class);
    }

    public function test_keys_are_rate_limited(): void
    {
        $key = $this->key();
        for ($i = 0; $i < \App\Http\Middleware\AuthenticateApiToken::PER_MINUTE; $i++) {
            $this->api($key)->getJson('/api/v1/exams');
        }
        $this->api($key)->getJson('/api/v1/exams')->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_owners_create_and_revoke_keys_and_only_the_hash_is_stored(): void
    {
        $response = $this->actingAs($this->owner)->post('/t/acme/settings/api', ['name' => 'HR sync', 'access' => 'write']);
        $plain = session('new_api_key');
        $response->assertRedirect(route('tenant.settings.api', 'acme'));
        $this->assertStringStartsWith('elib_', $plain);

        $token = ApiToken::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(hash('sha256', $plain), $token->token_hash);
        $this->assertTrue($token->can_write);
        $this->assertDatabaseMissing('api_tokens', ['token_hash' => $plain]);

        // Shown on the page right after creating it, and never again.
        $this->actingAs($this->owner)->get('/t/acme/settings/api')->assertOk()->assertSee($plain)->assertSee("won't be shown again", false);
        $this->actingAs($this->owner)->get('/t/acme/settings/api')->assertOk()->assertSee('HR sync')->assertSee('Read &amp; write', false)->assertDontSee($plain);

        $this->api($plain)->getJson('/api/v1/courses')->assertOk();
        $this->actingAs($this->owner)->delete("/t/acme/settings/api/{$token->id}")->assertRedirect();
        $this->api($plain)->getJson('/api/v1/courses')->assertUnauthorized();

        $this->actingAs($this->ada)->get('/t/acme/settings/api')->assertForbidden();
    }
}
