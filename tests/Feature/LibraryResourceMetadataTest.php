<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryResourceMetadataTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    public function test_owner_can_set_metadata_fields_and_tags(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Calculus Basics', 'slug' => 'calculus-basics', 'category' => 'Mathematics',
            'isbn' => '978-0-13-468599-1', 'publisher' => 'Pearson', 'publication_year' => 2019, 'language' => 'English',
            'tags' => 'calculus, revision, past-papers',
        ])->assertRedirect();

        $resource = LibraryResource::where('slug', 'calculus-basics')->first();
        $this->assertSame('978-0-13-468599-1', $resource->isbn);
        $this->assertSame('Pearson', $resource->publisher);
        $this->assertSame(2019, $resource->publication_year);
        $this->assertSame(3, $resource->tags()->count());
    }

    public function test_reusing_a_tag_name_across_resources_does_not_duplicate_it(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Book One', 'slug' => 'book-one', 'category' => 'Mathematics', 'tags' => 'revision',
        ]);
        $this->actingAs($owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Book Two', 'slug' => 'book-two', 'category' => 'Mathematics', 'tags' => 'revision',
        ]);

        $this->assertSame(1, \Elibrary\Library\Models\ResourceTag::where('tenant_id', $tenant->id)->where('slug', 'revision')->count());
    }

    public function test_tag_filter_narrows_the_catalog(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->actingAs($owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Tagged Book', 'slug' => 'tagged-book', 'category' => 'Mathematics', 'is_published' => '1', 'tags' => 'algebra',
        ]);
        $this->actingAs($owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Untagged Book', 'slug' => 'untagged-book', 'category' => 'Mathematics', 'is_published' => '1',
        ]);

        $member = User::factory()->create(['tenant_id' => $tenant->id]);
        $html = $this->actingAs($member)->get('/t/acme/library?tag=algebra')->assertOk()->getContent();

        $this->assertStringContainsString('Tagged Book', $html);
        $this->assertStringNotContainsString('Untagged Book', $html);
    }

    public function test_sort_by_newest_orders_most_recently_created_first(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->actingAs($owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Older Book', 'slug' => 'older-book', 'category' => 'Mathematics', 'is_published' => '1',
        ]);
        $this->actingAs($owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Newer Book', 'slug' => 'newer-book', 'category' => 'Mathematics', 'is_published' => '1',
        ]);

        $member = User::factory()->create(['tenant_id' => $tenant->id]);
        $html = $this->actingAs($member)->get('/t/acme/library?sort=newest')->assertOk()->getContent();

        $this->assertTrue(strpos($html, 'Newer Book') < strpos($html, 'Older Book'));
    }
}
