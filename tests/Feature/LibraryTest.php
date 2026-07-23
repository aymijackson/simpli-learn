<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithLibrary(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);

        return $tenant;
    }

    public function test_search_filters_resources_by_title(): void
    {
        $tenant = $this->tenantWithLibrary();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        LibraryResource::create(['tenant_id' => $tenant->id, 'title' => 'A Brief History of Time', 'slug' => 'a-brief-history-of-time', 'category' => 'Science']);
        LibraryResource::create(['tenant_id' => $tenant->id, 'title' => 'The Elements of Style', 'slug' => 'the-elements-of-style', 'category' => 'Language']);

        $response = $this->actingAs($user)->get('/t/acme/library?q=Elements');

        $response->assertOk();
        $response->assertSee('The Elements of Style');
        $response->assertDontSee('A Brief History of Time');
    }

    public function test_category_filter_narrows_results(): void
    {
        $tenant = $this->tenantWithLibrary();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        LibraryResource::create(['tenant_id' => $tenant->id, 'title' => 'A Brief History of Time', 'slug' => 'a-brief-history-of-time', 'category' => 'Science']);
        LibraryResource::create(['tenant_id' => $tenant->id, 'title' => 'The Elements of Style', 'slug' => 'the-elements-of-style', 'category' => 'Language']);

        $response = $this->actingAs($user)->get('/t/acme/library?category=Language');

        $response->assertOk();
        $response->assertSee('The Elements of Style');
        $response->assertDontSee('A Brief History of Time');
    }

    public function test_resource_detail_page_shows_the_open_link(): void
    {
        $tenant = $this->tenantWithLibrary();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $resource = LibraryResource::create([
            'tenant_id' => $tenant->id,
            'title' => 'A Brief History of Time',
            'slug' => 'a-brief-history-of-time',
            'category' => 'Science',
            'external_url' => 'https://example.org/resources/a-brief-history-of-time',
        ]);

        $response = $this->actingAs($user)->get("/t/acme/library/resources/{$resource->slug}");

        $response->assertOk();
        $response->assertSee('https://example.org/resources/a-brief-history-of-time', false);
    }

    public function test_a_tenant_without_the_library_module_gets_a_403(): void
    {
        $tenant = Tenant::create(['name' => 'Bright CBT', 'slug' => 'brightcbt', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/t/brightcbt/library')->assertForbidden();
    }
}
