<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Http\Controllers\ScormController;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ScormTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $learner;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->course = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Basics', 'slug' => 'basics', 'is_published' => true]);
        $this->lesson = $this->course->lessons()->create(['tenant_id' => $this->tenant->id, 'title' => 'Interactive', 'content' => 'x', 'position' => 0]);
    }

    /** @param  array<string, string>  $files */
    private function zip(array $files): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'scorm').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return new UploadedFile($path, 'package.zip', 'application/zip', null, true);
    }

    private function manifest(string $href = 'content/index.html', string $version = '1.2'): string
    {
        return <<<XML
            <?xml version="1.0"?>
            <manifest identifier="pkg" xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2">
              <metadata><schema>ADL SCORM</schema><schemaversion>{$version}</schemaversion></metadata>
              <organizations default="org1">
                <organization identifier="org1"><title>Course</title>
                  <item identifier="item1" identifierref="res1"><title>Lesson</title></item>
                </organization>
              </organizations>
              <resources><resource identifier="res1" type="webcontent" href="{$href}"><file href="{$href}"/></resource></resources>
            </manifest>
            XML;
    }

    private function upload(UploadedFile $zip)
    {
        return $this->actingAs($this->owner)->put("/t/acme/lms/manage/courses/basics/lessons/{$this->lesson->id}", [
            'title' => 'Interactive', 'content' => 'x', 'position' => 0, 'scorm_required' => 1, 'scorm_package' => $zip,
        ]);
    }

    private function uploadGood(string $version = '1.2'): void
    {
        $this->upload($this->zip(['imsmanifest.xml' => $this->manifest(version: $version), 'content/index.html' => '<h1>Hello SCORM</h1>', 'content/app.js' => 'var x = 1;']))
            ->assertSessionHasNoErrors();
        $this->lesson->refresh();
    }

    public function test_owners_upload_a_package_and_its_start_page_is_found(): void
    {
        $this->uploadGood();

        $this->assertSame('content/index.html', $this->lesson->scorm_launch);
        $this->assertSame('1.2', $this->lesson->scorm_version);
        Storage::disk('local')->assertExists($this->lesson->scorm_path.'/content/index.html');

        $this->uploadGood('2004 3rd Edition');
        $this->assertSame('2004', $this->lesson->scorm_version);
    }

    public function test_unsafe_or_broken_packages_are_refused(): void
    {
        $cases = [
            'no manifest' => ['index.html' => 'x'],
            'zip slip' => ['imsmanifest.xml' => $this->manifest('index.html'), 'index.html' => 'x', '../evil.html' => 'x'],
            'php file' => ['imsmanifest.xml' => $this->manifest('index.html'), 'index.html' => 'x', 'shell.php' => '<?php echo 1;'],
            'missing start page' => ['imsmanifest.xml' => $this->manifest('nope.html')],
            'start page outside' => ['imsmanifest.xml' => $this->manifest('../../etc/passwd'), 'index.html' => 'x'],
        ];

        foreach ($cases as $label => $files) {
            $this->upload($this->zip($files))->assertSessionHasErrors('scorm_package');
            $this->assertNull($this->lesson->fresh()->scorm_path, $label);
        }
        $this->assertSame([], Storage::disk('local')->allFiles('scorm'));
    }

    public function test_enrolled_learners_get_the_player_and_its_files(): void
    {
        $this->uploadGood();
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');

        $this->actingAs($this->learner)->get("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}")
            ->assertOk()->assertSee('data-scorm', false)->assertSee('scorm-runtime.js', false)
            ->assertSee('Finish the activity above to complete this lesson.')->assertDontSee('Mark as complete');

        $file = $this->actingAs($this->learner)->get("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}/scorm/content/index.html");
        $file->assertOk();
        $this->assertStringStartsWith('text/html', $file->headers->get('Content-Type'));
        $this->assertStringContainsString("'unsafe-eval'", $file->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('Hello SCORM', $file->streamedContent() ?: file_get_contents($file->baseResponse->getFile()->getPathname()));

        // No escaping the package folder.
        $this->actingAs($this->learner)->get("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}/scorm/../../../../.env")->assertNotFound();
        $this->actingAs($this->learner)->get("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}/scorm/content/missing.js")->assertNotFound();
    }

    public function test_people_not_enrolled_cannot_load_the_package(): void
    {
        $this->uploadGood();

        $this->actingAs($this->learner)->get("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}/scorm/content/index.html")->assertForbidden();
        $this->actingAs($this->learner)->postJson("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}/scorm-commit", ['state' => ['cmi.core.lesson_status' => 'passed']])->assertForbidden();
    }

    public function test_progress_is_saved_and_a_passed_package_completes_the_lesson(): void
    {
        $this->uploadGood();
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');
        $url = "/t/acme/lms/courses/basics/lessons/{$this->lesson->id}/scorm-commit";

        $this->actingAs($this->learner)->postJson($url, ['state' => ['cmi.core.lesson_status' => 'incomplete', 'cmi.suspend_data' => 'page=3', 'evil' => 'x']])
            ->assertOk()->assertJson(['completed' => false]);
        $progress = $this->lesson->progress()->withoutGlobalScopes()->where('user_id', $this->learner->id)->first();
        $this->assertSame(['cmi.core.lesson_status' => 'incomplete', 'cmi.suspend_data' => 'page=3'], $progress->scorm_state);
        $this->assertFalse($this->lesson->isCompletedBy($this->learner));

        // Coming back resumes from the saved data.
        $this->actingAs($this->learner)->get("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}")->assertSee('page=3', false);

        $this->actingAs($this->learner)->postJson($url, ['state' => ['cmi.core.lesson_status' => 'passed', 'cmi.core.score.raw' => '8', 'cmi.core.score.max' => '10']])
            ->assertJson(['completed' => true]);
        $this->assertTrue($this->lesson->isCompletedBy($this->learner));
        $this->assertEquals(80, $progress->fresh()->scorm_score);

        // Manual completion stays blocked while the package decides.
        $other = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($other)->post('/t/acme/lms/courses/basics/enroll');
        $this->actingAs($other)->post("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}/complete")->assertSessionHas('error');
        $this->assertFalse($this->lesson->isCompletedBy($other));
    }

    public function test_scorm_2004_completion_and_scaled_scores(): void
    {
        $this->assertTrue(ScormController::reportsCompletion(['cmi.completion_status' => 'completed'], '2004'));
        $this->assertTrue(ScormController::reportsCompletion(['cmi.success_status' => 'passed'], '2004'));
        $this->assertFalse(ScormController::reportsCompletion(['cmi.success_status' => 'failed', 'cmi.completion_status' => 'incomplete'], '2004'));
        $this->assertFalse(ScormController::reportsCompletion(['cmi.core.lesson_status' => 'failed'], '1.2'));
        $this->assertSame(75.0, ScormController::score(['cmi.score.scaled' => '0.75'], '2004'));
        $this->assertSame(42.0, ScormController::score(['cmi.core.score.raw' => '42'], '1.2'));
        $this->assertNull(ScormController::score([], '1.2'));
    }

    public function test_optional_completion_and_removing_the_package(): void
    {
        $this->uploadGood();
        $this->lesson->update(['scorm_required' => false]);
        $this->actingAs($this->learner)->post('/t/acme/lms/courses/basics/enroll');
        $this->actingAs($this->learner)->post("/t/acme/lms/courses/basics/lessons/{$this->lesson->id}/complete")->assertSessionHas('status');

        $path = $this->lesson->scorm_path;
        $this->actingAs($this->owner)->put("/t/acme/lms/manage/courses/basics/lessons/{$this->lesson->id}", [
            'title' => 'Interactive', 'content' => 'x', 'position' => 0, 'remove_scorm' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertNull($this->lesson->fresh()->scorm_path);
        Storage::disk('local')->assertMissing($path.'/content/index.html');
    }
}
