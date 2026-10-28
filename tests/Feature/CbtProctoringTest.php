<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Models\ExamProctoringSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CbtProctoringTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function exam(array $attributes = []): Exam
    {
        $exam = Exam::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'title' => 'Final',
            'slug' => 'final',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
            'allow_retakes' => true,
        ], $attributes));

        $question = $exam->questions()->create(['tenant_id' => $this->tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $question->options()->create(['tenant_id' => $this->tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);

        return $exam;
    }

    private function webcamExam(array $attributes = []): Exam
    {
        return $this->exam(array_merge(['integrity_monitoring_enabled' => true, 'webcam_proctoring' => true], $attributes));
    }

    private function jpeg(int $width = 64, int $height = 48): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($image);

        return ob_get_clean();
    }

    private function idPhoto(): string
    {
        return 'data:image/jpeg;base64,'.base64_encode($this->jpeg());
    }

    private function start(Exam $exam, array $data = [], string $ip = '127.0.0.1')
    {
        return $this->actingAs($this->learner)
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from("/t/acme/cbt/exams/{$exam->slug}")
            ->post("/t/acme/cbt/exams/{$exam->slug}/start", $data);
    }

    private function attemptFor(Exam $exam): ?ExamAttempt
    {
        return $exam->attempts()->where('user_id', $this->learner->id)->first();
    }

    private function snapshots(): \Illuminate\Database\Eloquent\Builder
    {
        return ExamProctoringSnapshot::withoutGlobalScopes();
    }

    // ---------------------------------------------------------------- settings

    public function test_owners_choose_a_proctoring_level_and_supervision(): void
    {
        $this->actingAs($this->owner)->post('/t/acme/cbt/manage/exams', [
            'title' => 'Board exam', 'slug' => 'board-exam', 'duration_minutes' => 40, 'pass_percentage' => 80,
            'proctoring_level' => 'webcam', 'snapshot_interval_seconds' => 30, 'proctoring_retention_days' => 60,
            'invigilator_code' => ' room-7 ', 'allowed_networks' => "102.89.34.0/24, 41.58.1.10",
        ])->assertRedirect();

        $exam = Exam::withoutGlobalScopes()->where('slug', 'board-exam')->sole();
        $this->assertTrue($exam->webcam_proctoring);
        $this->assertTrue($exam->integrity_monitoring_enabled);
        $this->assertSame('webcam', $exam->proctoringLevel());
        $this->assertSame(30, $exam->snapshot_interval_seconds);
        $this->assertSame(60, $exam->proctoring_retention_days);
        $this->assertSame('room-7', $exam->invigilator_code);
        $this->assertSame(['102.89.34.0/24', '41.58.1.10'], $exam->allowedNetworks());
        // The code is encrypted at rest.
        $this->assertNotSame('room-7', DB::table('exams')->where('id', $exam->id)->value('invigilator_code'));

        $this->actingAs($this->owner)->put("/t/acme/cbt/manage/exams/{$exam->slug}", [
            'title' => 'Board exam', 'slug' => 'board-exam', 'duration_minutes' => 40, 'pass_percentage' => 80,
            'proctoring_level' => 'browser', 'invigilator_code' => '', 'allowed_networks' => '',
        ])->assertRedirect();

        $exam->refresh();
        $this->assertSame('browser', $exam->proctoringLevel());
        $this->assertNull($exam->invigilator_code);
        $this->assertSame([], $exam->allowedNetworks());
    }

    public function test_allowed_networks_must_be_ip_addresses_or_ranges(): void
    {
        $this->actingAs($this->owner)->post('/t/acme/cbt/manage/exams', [
            'title' => 'Board exam', 'slug' => 'board-exam', 'duration_minutes' => 40, 'pass_percentage' => 80,
            'allowed_networks' => "102.89.34.0/24\nmy-office",
        ])->assertSessionHasErrors('allowed_networks');

        $this->assertFalse(Exam::withoutGlobalScopes()->where('slug', 'board-exam')->exists());
    }

    // -------------------------------------------------------------- start checks

    public function test_a_webcam_exam_needs_consent_and_an_id_photo_to_start(): void
    {
        $exam = $this->webcamExam();

        $this->start($exam)->assertSessionHasErrors(['proctoring_consent', 'id_photo']);
        $this->start($exam, ['proctoring_consent' => '1', 'id_photo' => 'data:image/jpeg;base64,bm90IGFuIGltYWdl'])
            ->assertSessionHasErrors('id_photo');
        $this->assertNull($this->attemptFor($exam));

        $this->start($exam, ['proctoring_consent' => '1', 'id_photo' => $this->idPhoto()])->assertRedirect();

        $attempt = $this->attemptFor($exam);
        $this->assertNotNull($attempt->proctoring_consented_at);
        $photo = $this->snapshots()->where('exam_attempt_id', $attempt->id)->sole();
        $this->assertSame('id_check', $photo->kind);
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_the_exam_page_shows_the_camera_check_and_allows_the_camera(): void
    {
        $exam = $this->webcamExam();

        $this->actingAs($this->learner)->get("/t/acme/cbt/exams/{$exam->slug}")
            ->assertOk()
            ->assertSee('data-proctor-check', false)
            ->assertSee('js/exam-proctor.js', false)
            ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(self)');

        // Everywhere else the camera stays off.
        $this->actingAs($this->learner)->get('/t/acme/cbt')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');
    }

    public function test_the_attempt_page_runs_the_proctoring_session(): void
    {
        $exam = $this->webcamExam();
        $this->start($exam, ['proctoring_consent' => '1', 'id_photo' => $this->idPhoto()]);
        $attempt = $this->attemptFor($exam);

        $this->actingAs($this->learner)->get("/t/acme/cbt/attempts/{$attempt->id}")
            ->assertOk()
            ->assertSee('data-proctor-session', false)
            ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(self)');
    }

    public function test_an_invigilator_code_is_needed_when_set(): void
    {
        $exam = $this->exam(['invigilator_code' => 'ROOM7']);

        $this->start($exam)->assertSessionHasErrors('invigilator_code');
        $this->start($exam, ['invigilator_code' => 'ROOM8'])->assertSessionHasErrors('invigilator_code');
        $this->assertNull($this->attemptFor($exam));

        $this->start($exam, ['invigilator_code' => ' room7 '])->assertSessionHasNoErrors();
        $this->assertNotNull($this->attemptFor($exam));
    }

    public function test_an_exam_limited_to_networks_only_starts_and_runs_from_them(): void
    {
        $exam = $this->exam(['allowed_networks' => "102.89.34.0/24\n41.58.1.10"]);

        $this->start($exam, [], '8.8.8.8')->assertSessionHas('error');
        $this->assertNull($this->attemptFor($exam));

        $this->start($exam, [], '102.89.34.77')->assertRedirect();
        $attempt = $this->attemptFor($exam);
        $this->assertNotNull($attempt);

        $this->actingAs($this->learner)->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->get("/t/acme/cbt/attempts/{$attempt->id}")
            ->assertRedirect("/t/acme/cbt/exams/{$exam->slug}");

        $this->actingAs($this->learner)->withServerVariables(['REMOTE_ADDR' => '41.58.1.10'])
            ->get("/t/acme/cbt/attempts/{$attempt->id}")
            ->assertOk();
    }

    // -------------------------------------------------------------- snapshots

    private function startedWebcamAttempt(array $attributes = []): ExamAttempt
    {
        $exam = $this->webcamExam($attributes);
        $this->start($exam, ['proctoring_consent' => '1', 'id_photo' => $this->idPhoto()]);

        return $this->attemptFor($exam);
    }

    private function upload(ExamAttempt $attempt, ?User $user = null, array $data = [])
    {
        return $this->actingAs($user ?? $this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}/snapshots", array_merge([
            'image' => UploadedFile::fake()->createWithContent('snapshot.jpg', $this->jpeg()),
            'kind' => 'interval',
        ], $data));
    }

    public function test_photos_are_stored_during_a_proctored_attempt(): void
    {
        $attempt = $this->startedWebcamAttempt();

        $this->upload($attempt)->assertNoContent();
        $this->upload($attempt, null, ['kind' => 'event', 'event_type' => 'fullscreen_exit'])->assertNoContent();

        $photos = $this->snapshots()->where('exam_attempt_id', $attempt->id)->where('kind', '!=', 'id_check')->orderBy('id')->get();
        $this->assertSame(['interval', 'event'], $photos->pluck('kind')->all());
        $this->assertSame('fullscreen_exit', $photos[1]->event_type->value);
        $photos->each(fn ($photo) => Storage::disk('local')->assertExists($photo->path));
    }

    public function test_photos_are_refused_when_they_should_not_be_taken(): void
    {
        $attempt = $this->startedWebcamAttempt();

        // Someone else's attempt.
        $this->upload($attempt, User::factory()->create(['tenant_id' => $this->tenant->id]))->assertForbidden();
        // Not an image.
        $this->upload($attempt, null, ['image' => UploadedFile::fake()->createWithContent('snapshot.jpg', '<?php echo 1;')])->assertStatus(422);

        // After submitting.
        $attempt->forceFill(['submitted_at' => now()])->save();
        $this->upload($attempt)->assertNotFound();

        // An exam without webcam proctoring.
        $plain = $this->exam(['slug' => 'plain']);
        $this->start($plain);
        $this->upload($this->attemptFor($plain))->assertNotFound();

        $this->assertSame(1, $this->snapshots()->count()); // just the ID check
    }

    public function test_new_event_types_are_logged_for_webcam_exams(): void
    {
        $attempt = $this->startedWebcamAttempt();

        foreach (['fullscreen_exit', 'camera_off', 'multiple_screens'] as $type) {
            $this->actingAs($this->learner)->post("/t/acme/cbt/attempts/{$attempt->id}/integrity-events", ['event_type' => $type])->assertNoContent();
        }

        $this->assertSame(3, $attempt->integrityEvents()->count());
    }

    // ----------------------------------------------------------------- review

    public function test_owners_review_photos_and_record_a_verdict(): void
    {
        $attempt = $this->startedWebcamAttempt();
        $this->upload($attempt, null, ['kind' => 'event', 'event_type' => 'window_blur']);
        $attempt->forceFill(['submitted_at' => now(), 'score' => 100])->save();
        $exam = $attempt->exam;
        $photo = $this->snapshots()->where('exam_attempt_id', $attempt->id)->where('kind', 'event')->sole();

        $this->actingAs($this->owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}")->assertOk()->assertSee('Review photos');

        $this->actingAs($this->owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}/attempts/{$attempt->id}/integrity")
            ->assertOk()
            ->assertSee('ID check')
            ->assertSee('Exam window lost focus');

        $this->actingAs($this->owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}/attempts/{$attempt->id}/snapshots/{$photo->id}")
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($this->owner)->put("/t/acme/cbt/manage/analytics/{$exam->slug}/attempts/{$attempt->id}/review", [
            'review_status' => 'cleared',
            'review_notes' => 'Looked away to the window; no one else present.',
        ])->assertRedirect();

        $attempt->refresh();
        $this->assertSame('cleared', $attempt->review_status);
        $this->assertSame($this->owner->id, $attempt->reviewed_by);
        $this->actingAs($this->owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}")->assertSee('Cleared');
    }

    public function test_learners_cannot_see_review_photos_and_photos_stay_with_their_attempt(): void
    {
        $attempt = $this->startedWebcamAttempt();
        $exam = $attempt->exam;
        $photo = $this->snapshots()->where('exam_attempt_id', $attempt->id)->sole();

        $this->actingAs($this->learner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}/attempts/{$attempt->id}/snapshots/{$photo->id}")
            ->assertForbidden();

        $other = ExamAttempt::create(['tenant_id' => $this->tenant->id, 'exam_id' => $exam->id, 'user_id' => $this->owner->id, 'started_at' => now()]);
        $this->actingAs($this->owner)->get("/t/acme/cbt/manage/analytics/{$exam->slug}/attempts/{$other->id}/snapshots/{$photo->id}")
            ->assertNotFound();
    }

    // -------------------------------------------------------------- retention

    public function test_photos_are_deleted_once_the_retention_period_has_passed(): void
    {
        $old = $this->startedWebcamAttempt(['proctoring_retention_days' => 30]);
        $old->forceFill(['submitted_at' => now()->subDays(31)])->save();
        $oldPhoto = $this->snapshots()->where('exam_attempt_id', $old->id)->sole();

        $recent = $this->startedWebcamAttempt(['slug' => 'final-2', 'proctoring_retention_days' => 30]);
        $recent->forceFill(['submitted_at' => now()->subDays(5)])->save();

        $inProgress = $this->startedWebcamAttempt(['slug' => 'final-3', 'proctoring_retention_days' => 1]);

        $this->artisan('model:prune', ['--model' => [ExamProctoringSnapshot::class]])->assertSuccessful();

        $this->assertFalse($this->snapshots()->whereKey($oldPhoto->id)->exists());
        Storage::disk('local')->assertMissing($oldPhoto->path);
        $this->assertTrue($this->snapshots()->where('exam_attempt_id', $recent->id)->exists());
        $this->assertTrue($this->snapshots()->where('exam_attempt_id', $inProgress->id)->exists());
    }
}
