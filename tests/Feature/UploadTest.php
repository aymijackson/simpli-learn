<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_owner_can_upload_an_image(): void
    {
        Storage::fake('public');

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        $response = $this->actingAs($owner)->post('/t/acme/uploads', [
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['url']);
        $this->assertStringContainsString("uploads/{$tenant->id}/", $response->json('url'));

        Storage::disk('public')->assertExists(
            "uploads/{$tenant->id}/".basename(parse_url($response->json('url'), PHP_URL_PATH))
        );
    }

    public function test_a_tenant_member_cannot_upload(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($member)
            ->post('/t/acme/uploads', ['file' => UploadedFile::fake()->image('photo.jpg')])
            ->assertForbidden();
    }

    public function test_a_guest_cannot_upload(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);

        $this->post('/t/acme/uploads', ['file' => UploadedFile::fake()->image('photo.jpg')])
            ->assertRedirect('/t/acme/login');
    }

    public function test_a_central_admin_can_upload(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['tenant_id' => null]);

        $response = $this->actingAs($admin)->post('/admin/uploads', [
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertOk();
        $this->assertStringContainsString('uploads/central/', $response->json('url'));
    }

    public function test_an_oversized_file_is_rejected(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)
            ->post('/t/acme/uploads', ['file' => UploadedFile::fake()->image('huge.jpg')->size(10241)])
            ->assertSessionHasErrors('file');
    }

    public function test_a_disallowed_file_type_is_rejected(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)
            ->post('/t/acme/uploads', ['file' => UploadedFile::fake()->create('malicious.php', 10)])
            ->assertSessionHasErrors('file');
    }
}
