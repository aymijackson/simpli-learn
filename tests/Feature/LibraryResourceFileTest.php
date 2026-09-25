<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryResourceFileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function openResource(Tenant $tenant, array $attributes = []): LibraryResource
    {
        return LibraryResource::create(array_merge([
            'tenant_id' => $tenant->id, 'title' => 'Physics 101', 'slug' => 'physics-101',
            'category' => 'Science', 'is_published' => true, 'requires_checkout' => false,
        ], $attributes));
    }

    public function test_owner_can_upload_an_open_and_a_secure_file(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = $this->openResource($tenant);

        $this->actingAs($owner)->post("/t/acme/library/manage/resources/physics-101/files", [
            'title' => 'Summary sheet', 'format' => 'file', 'access_level' => 'open',
            'file' => UploadedFile::fake()->create('summary.txt', 100, 'text/plain'),
        ])->assertRedirect();

        $this->actingAs($owner)->post("/t/acme/library/manage/resources/physics-101/files", [
            'title' => 'Full text', 'format' => 'pdf', 'access_level' => 'secure',
            'file' => UploadedFile::fake()->create('book.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame(2, $resource->fresh()->files()->count());
    }

    public function test_an_open_file_is_downloadable_by_any_tenant_member(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = $this->openResource($tenant);
        $member = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)->post("/t/acme/library/manage/resources/physics-101/files", [
            'title' => 'Summary sheet', 'format' => 'file', 'access_level' => 'open',
            'file' => UploadedFile::fake()->create('summary.txt', 100, 'text/plain'),
        ]);
        $file = $resource->fresh()->files()->first();

        $this->actingAs($member)
            ->get("/t/acme/library/resources/physics-101/files/{$file->id}/download")
            ->assertRedirect();

        Storage::disk('public')->assertExists($file->disk_path);
    }

    public function test_a_secure_file_is_streamable_on_a_resource_that_does_not_require_checkout(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = $this->openResource($tenant);
        $member = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)->post("/t/acme/library/manage/resources/physics-101/files", [
            'title' => 'Full text', 'format' => 'pdf', 'access_level' => 'secure',
            'file' => UploadedFile::fake()->create('book.pdf', 100, 'application/pdf'),
        ]);
        $file = $resource->fresh()->files()->first();

        $this->actingAs($member)
            ->get("/t/acme/library/resources/physics-101/files/{$file->id}/stream")
            ->assertOk();
    }

    public function test_a_secure_file_on_a_checkout_required_resource_is_blocked_without_an_active_checkout(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = $this->openResource($tenant, ['requires_checkout' => true]);
        $member = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)->post("/t/acme/library/manage/resources/physics-101/files", [
            'title' => 'Full text', 'format' => 'pdf', 'access_level' => 'secure',
            'file' => UploadedFile::fake()->create('book.pdf', 100, 'application/pdf'),
        ]);
        $file = $resource->fresh()->files()->first();

        $this->actingAs($member)
            ->get("/t/acme/library/resources/physics-101/files/{$file->id}/stream")
            ->assertForbidden();
    }

    public function test_secure_files_are_never_reachable_via_a_public_url(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = $this->openResource($tenant);

        $this->actingAs($owner)->post("/t/acme/library/manage/resources/physics-101/files", [
            'title' => 'Full text', 'format' => 'pdf', 'access_level' => 'secure',
            'file' => UploadedFile::fake()->create('book.pdf', 100, 'application/pdf'),
        ]);
        $file = $resource->fresh()->files()->first();

        Storage::disk('public')->assertMissing($file->disk_path);
        Storage::disk('local')->assertExists($file->disk_path);
    }

    public function test_tenant_isolation_on_file_upload(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner('acme');
        [$tenantB, $ownerB] = $this->tenantWithOwner('other');
        $resourceA = $this->openResource($tenantA);

        $this->actingAs($ownerB)
            ->post("/t/other/library/manage/resources/physics-101/files", [
                'title' => 'Summary sheet', 'format' => 'file', 'access_level' => 'open',
                'file' => UploadedFile::fake()->create('summary.pdf', 100, 'application/pdf'),
            ])
            ->assertNotFound();
    }
}
