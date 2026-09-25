<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    public function test_a_member_cannot_access_analytics(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);

        $this->actingAs($member)->get('/t/acme/library/manage/analytics')->assertForbidden();
    }

    public function test_the_overview_reports_correct_aggregate_numbers(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $resource = LibraryResource::create([
            'tenant_id' => $tenant->id, 'title' => 'Physics 101', 'slug' => 'physics-101',
            'category' => 'Science', 'is_published' => true, 'requires_checkout' => true,
        ]);
        $userA = User::factory()->create(['tenant_id' => $tenant->id]);
        $userB = User::factory()->create(['tenant_id' => $tenant->id]);

        DB::table('library_checkouts')->insert([
            ['tenant_id' => $tenant->id, 'resource_id' => $resource->id, 'user_id' => $userA->id, 'checked_out_at' => now()->subDays(10), 'due_at' => now()->subDays(-4), 'returned_at' => now()->subDays(5), 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $tenant->id, 'resource_id' => $resource->id, 'user_id' => $userB->id, 'checked_out_at' => now(), 'due_at' => now()->addDays(14), 'returned_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $html = $this->actingAs($owner)->get('/t/acme/library/manage/analytics')->assertOk()->getContent();

        $this->assertStringContainsString('Physics 101', $html);
        $this->assertStringContainsString('2 checkouts', $html);
    }

    public function test_csv_export_contains_a_header_row_and_resource_data(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        LibraryResource::create(['tenant_id' => $tenant->id, 'title' => 'Physics 101', 'slug' => 'physics-101', 'category' => 'Science', 'is_published' => true]);

        $response = $this->actingAs($owner)->get('/t/acme/library/manage/analytics/export.csv');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('Resource,Category', $content);
        $this->assertStringContainsString('Total checkouts', $content);
        $this->assertStringContainsString('Physics 101', $content);
    }

    public function test_another_tenants_resources_never_appear_in_this_tenants_analytics(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner();
        $tenantB = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        $tenantB->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        LibraryResource::create(['tenant_id' => $tenantB->id, 'title' => 'Other Resource', 'slug' => 'other-resource', 'category' => 'Fiction', 'is_published' => true]);

        $html = $this->actingAs($ownerA)->get('/t/acme/library/manage/analytics')->assertOk()->getContent();

        $this->assertStringNotContainsString('Other Resource', $html);
    }
}
