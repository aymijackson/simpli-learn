<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Documents\DocumentConverter;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryFileTypesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->member = User::factory()->create(['tenant_id' => $this->tenant->id]);
        LibraryResource::create([
            'tenant_id' => $this->tenant->id, 'title' => 'Handbook', 'slug' => 'handbook', 'category' => 'HR',
            'is_published' => true, 'requires_checkout' => false,
        ]);
    }

    /** Swap in a converter that "works" (or not) without LibreOffice. */
    private function converter(bool $available, bool $succeeds = true): void
    {
        $this->app->instance(DocumentConverter::class, new class($available, $succeeds) implements DocumentConverter
        {
            public function __construct(private bool $available, private bool $succeeds)
            {
            }

            public function isAvailable(): bool
            {
                return $this->available;
            }

            public function toPdf(string $sourcePath, string $pdfPath): bool
            {
                if (! $this->succeeds) {
                    return false;
                }
                @mkdir(dirname($pdfPath), 0777, true);

                return (bool) file_put_contents($pdfPath, '%PDF-1.4 converted from '.basename($sourcePath));
            }
        });
    }

    private function upload(UploadedFile $file, string $access = 'secure', string $format = 'auto')
    {
        return $this->actingAs($this->owner)->from('/t/acme/library/manage/resources/handbook/edit')
            ->post('/t/acme/library/manage/resources/handbook/files', ['title' => 'Item', 'format' => $format, 'access_level' => $access, 'file' => $file]);
    }

    private function lastFile(): ?LibraryResourceFile
    {
        return LibraryResourceFile::withoutGlobalScopes()->latest('id')->first();
    }

    private function url(LibraryResourceFile $file, string $action): string
    {
        return "/t/acme/library/resources/handbook/files/{$file->id}/{$action}";
    }

    public function test_the_format_is_detected_from_the_file(): void
    {
        $this->converter(true);
        foreach ([
            ['photo.JPG', 'image'], ['talk.mp4', 'video'], ['lecture.m4a', 'audio'], ['notes.txt', 'text'],
            ['report.docx', 'document'], ['budget.xlsx', 'document'], ['slides.pptx', 'document'], ['book.epub', 'epub'],
            ['paper.pdf', 'pdf'], ['bundle.zip', 'file'],
        ] as [$name, $format]) {
            $this->upload(UploadedFile::fake()->create($name, 20), 'open')->assertSessionHasNoErrors();
            $this->assertSame($format, $this->lastFile()->format->value, $name);
        }
    }

    public function test_unsupported_and_mismatched_types_are_refused_on_the_form(): void
    {
        $this->upload(UploadedFile::fake()->create('drawing.svg', 5))->assertSessionHasErrors('file');
        $this->upload(UploadedFile::fake()->create('page.html', 5))->assertSessionHasErrors('file');
        $this->upload(UploadedFile::fake()->create('photo.png', 5), 'secure', 'video')->assertSessionHasErrors('file');
        $this->assertSame(0, LibraryResourceFile::withoutGlobalScopes()->count());
    }

    public function test_office_files_are_viewed_securely_through_a_pdf_copy(): void
    {
        $this->converter(true);
        $this->upload(UploadedFile::fake()->create('policy.docx', 30))->assertSessionHasNoErrors();
        $file = $this->lastFile();

        $this->assertNotNull($file->pdf_path);
        Storage::disk('local')->assertExists($file->pdf_path);

        $stream = $this->actingAs($this->member)->get($this->url($file, 'stream'));
        $stream->assertOk();
        $this->assertSame('application/pdf', $stream->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename=policy.pdf', $stream->headers->get('Content-Disposition'));

        $this->actingAs($this->member)->get($this->url($file, 'read'))->assertOk()->assertSee('data-pdf-reader', false);
        // Secure: the original .docx is never handed out.
        $this->actingAs($this->member)->get($this->url($file, 'download'))->assertNotFound();
        $this->actingAs($this->member)->get('/t/acme/library/resources/handbook')->assertSee('Read')->assertDontSee('Download');
    }

    public function test_a_secure_office_file_is_refused_when_the_server_cannot_convert(): void
    {
        $this->converter(false);

        $this->upload(UploadedFile::fake()->create('policy.docx', 30))
            ->assertSessionHasErrors(['file' => 'This server can’t convert Word, Excel or PowerPoint files for secure viewing. Save the file as a PDF and upload that instead, or choose “Open” access to let people download it.']);

        $this->assertSame(0, LibraryResourceFile::withoutGlobalScopes()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_failed_conversion_is_refused_too(): void
    {
        $this->converter(true, succeeds: false);

        $this->upload(UploadedFile::fake()->create('policy.docx', 30))->assertSessionHasErrors('file');
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_an_open_office_file_without_a_converter_is_a_download(): void
    {
        $this->converter(false);
        $this->upload(UploadedFile::fake()->create('policy.docx', 30), 'open')->assertSessionHasNoErrors();
        $file = $this->lastFile();

        $this->assertNull($file->pdf_path);
        $this->actingAs($this->member)->get($this->url($file, 'stream'))->assertNotFound();
        $this->actingAs($this->member)->get($this->url($file, 'read'))->assertNotFound();
        $this->assertSame(200, $this->actingAs($this->member)->get($this->url($file, 'download'))->baseResponse->getStatusCode());
    }

    public function test_media_opens_in_the_secure_viewer(): void
    {
        foreach (['photo.png' => '<img', 'talk.mp4' => 'controlslist="nodownload', 'lecture.mp3' => '<audio'] as $name => $marker) {
            $this->upload(UploadedFile::fake()->create($name, 20))->assertSessionHasNoErrors();
            $file = $this->lastFile();

            $this->actingAs($this->member)->get($this->url($file, 'read'))->assertOk()->assertSee($marker, false);
            $this->assertSame(200, $this->actingAs($this->member)->get($this->url($file, 'stream'))->baseResponse->getStatusCode());
            $this->actingAs($this->member)->get($this->url($file, 'download'))->assertNotFound();
        }
    }

    public function test_video_supports_seeking(): void
    {
        $this->upload(UploadedFile::fake()->createWithContent('talk.mp4', str_repeat('v', 1000)));
        $file = $this->lastFile();

        $response = $this->actingAs($this->member)->get($this->url($file, 'stream'), ['Range' => 'bytes=0-99']);

        $this->assertSame(206, $response->baseResponse->getStatusCode());
        $this->assertSame('video/mp4', $response->headers->get('Content-Type'));
    }

    public function test_text_is_shown_escaped(): void
    {
        $this->upload(UploadedFile::fake()->createWithContent('notes.txt', "Line one\n<script>alert(1)</script>"));
        $file = $this->lastFile();

        $this->actingAs($this->member)->get($this->url($file, 'read'))
            ->assertOk()->assertSee('Line one')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)', false);
    }

    public function test_a_disguised_file_is_never_served_as_a_web_page(): void
    {
        $this->upload(UploadedFile::fake()->createWithContent('photo.jpg', '<html><script>alert(1)</script></html>'));
        $file = $this->lastFile();

        $response = $this->actingAs($this->member)->get($this->url($file, 'stream'));

        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringStartsWith('sandbox', $response->headers->get('Content-Security-Policy'));
    }

    public function test_viewing_still_needs_access(): void
    {
        LibraryResource::withoutGlobalScopes()->where('slug', 'handbook')->update(['requires_checkout' => true, 'total_copies' => 1]);
        $this->upload(UploadedFile::fake()->create('photo.png', 20));
        $file = $this->lastFile();

        $this->actingAs($this->member)->get($this->url($file, 'read'))->assertForbidden();
        $this->actingAs($this->member)->get($this->url($file, 'stream'))->assertForbidden();
    }

    public function test_deleting_a_file_removes_its_pdf_copy(): void
    {
        $this->converter(true);
        $this->upload(UploadedFile::fake()->create('policy.docx', 30));
        $file = $this->lastFile();

        $this->actingAs($this->owner)->delete("/t/acme/library/manage/resources/handbook/files/{$file->id}")->assertRedirect();

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_older_uploads_are_sorted_into_viewable_kinds(): void
    {
        $resourceId = LibraryResource::withoutGlobalScopes()->value('id');
        foreach (['old.docx', 'old.txt', 'old.zip'] as $name) {
            DB::table('library_resource_files')->insert(['tenant_id' => $this->tenant->id, 'resource_id' => $resourceId, 'title' => $name, 'format' => 'file', 'access_level' => 'secure', 'disk_path' => "library-files/1/{$name}", 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Run the real migration's data step: roll it back, then forward again.
        $migration = require base_path('packages/digital-library/database/migrations/2026_09_28_000003_add_pdf_copies_to_library_resource_files.php');
        $migration->down();
        $migration->up();

        $this->assertSame(['document', 'text', 'file'], DB::table('library_resource_files')->orderBy('id')->pluck('format')->all());
    }

    public function test_the_convert_command_backfills_pdf_copies(): void
    {
        $this->converter(false);
        $this->upload(UploadedFile::fake()->create('policy.docx', 30), 'open');
        $this->assertNull($this->lastFile()->pdf_path);

        $this->converter(true);
        $this->artisan('library:convert-documents')->assertSuccessful();

        $this->assertNotNull($this->lastFile()->pdf_path);
        $this->assertSame(200, $this->actingAs($this->member)->get($this->url($this->lastFile(), 'stream'))->baseResponse->getStatusCode());
    }

    public function test_the_convert_command_explains_a_missing_libreoffice(): void
    {
        $this->converter(false);

        $this->artisan('library:convert-documents')->expectsOutputToContain('LibreOffice was not found')->assertFailed();
    }
}
