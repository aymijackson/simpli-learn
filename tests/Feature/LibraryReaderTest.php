<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryReadingProgress;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function resourceWithPdf(Tenant $tenant, array $resourceAttributes = []): array
    {
        $resource = LibraryResource::create(array_merge([
            'tenant_id' => $tenant->id, 'title' => 'Physics 101', 'slug' => 'physics-101',
            'category' => 'Science', 'is_published' => true, 'requires_checkout' => false,
        ], $resourceAttributes));

        $file = $resource->files()->create([
            'tenant_id' => $tenant->id, 'title' => 'Full text', 'format' => 'pdf', 'access_level' => 'secure',
            'disk_path' => 'secure-library-files/'.$tenant->id.'/book.pdf', 'mime_type' => 'application/pdf',
            'original_filename' => 'book.pdf', 'size' => 100, 'position' => 0,
        ]);
        Storage::disk('local')->put($file->disk_path, 'fake-pdf-bytes');

        return [$resource, $file];
    }

    public function test_reader_403s_without_access_on_a_checkout_required_resource(): void
    {
        [$tenant] = $this->tenantWithOwner();
        [$resource, $file] = $this->resourceWithPdf($tenant, ['requires_checkout' => true]);
        $member = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($member)
            ->get("/t/acme/library/resources/physics-101/files/{$file->id}/read")
            ->assertForbidden();
    }

    public function test_reader_200s_when_accessible(): void
    {
        [$tenant] = $this->tenantWithOwner();
        [$resource, $file] = $this->resourceWithPdf($tenant);
        $member = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($member)
            ->get("/t/acme/library/resources/physics-101/files/{$file->id}/read")
            ->assertOk();
    }

    public function test_progress_saves_and_is_returned_on_next_reader_load(): void
    {
        [$tenant] = $this->tenantWithOwner();
        [$resource, $file] = $this->resourceWithPdf($tenant);
        $member = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($member)
            ->post("/t/acme/library/resources/physics-101/files/{$file->id}/progress", ['position' => '7'])
            ->assertNoContent();

        $progress = LibraryReadingProgress::where('resource_file_id', $file->id)->where('user_id', $member->id)->first();
        $this->assertSame('7', $progress->position);

        $html = $this->actingAs($member)
            ->get("/t/acme/library/resources/physics-101/files/{$file->id}/read")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-initial-page="7"', $html);
    }

    public function test_progress_update_is_blocked_without_access(): void
    {
        [$tenant] = $this->tenantWithOwner();
        [$resource, $file] = $this->resourceWithPdf($tenant, ['requires_checkout' => true]);
        $member = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($member)
            ->post("/t/acme/library/resources/physics-101/files/{$file->id}/progress", ['position' => '3'])
            ->assertForbidden();
    }

    public function test_tenant_isolation_on_reading_progress(): void
    {
        [$tenantA] = $this->tenantWithOwner('acme');
        [$tenantB] = $this->tenantWithOwner('other');
        [$resourceA, $fileA] = $this->resourceWithPdf($tenantA);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($userB)
            ->get("/t/other/library/resources/physics-101/files/{$fileA->id}/read")
            ->assertNotFound();
    }
}
