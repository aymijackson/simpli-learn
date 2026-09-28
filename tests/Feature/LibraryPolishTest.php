<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryPaymentGatewayCredential;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Elibrary\Lms\Models\CoursePaymentGatewayCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LibraryPolishTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        foreach ([Module::Library, Module::Lms] as $module) {
            $this->tenant->tenantModules()->create(['module' => $module, 'is_enabled' => true]);
        }
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->member = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function resource(array $attributes = []): LibraryResource
    {
        return LibraryResource::create(array_merge([
            'tenant_id' => $this->tenant->id, 'title' => 'Book', 'slug' => 'book-'.Str::random(6), 'category' => 'General',
            'is_published' => true, 'requires_checkout' => false,
        ], $attributes));
    }

    // --- Catalogue ------------------------------------------------------------

    public function test_the_catalogue_is_paginated(): void
    {
        foreach (range(1, 30) as $i) {
            $this->resource(['title' => sprintf('Book %02d', $i)]);
        }

        $this->actingAs($this->member)->get('/t/acme/library')
            ->assertOk()->assertSee('30 resources')->assertSee('Book 01')->assertDontSee('Book 30')->assertSee('page=2', false);
        $this->actingAs($this->member)->get('/t/acme/library?page=2')->assertSee('Book 30')->assertDontSee('Book 01');
    }

    public function test_search_treats_percent_and_underscore_literally(): void
    {
        $this->resource(['title' => 'Growth of 50% in a year']);
        $this->resource(['title' => 'Plain title']);

        $this->actingAs($this->member)->get('/t/acme/library?q=%25')->assertSee('Growth of 50% in a year')->assertDontSee('Plain title');
        $this->actingAs($this->member)->get('/t/acme/library?q=_')->assertSee('No resources found');
    }

    // --- Analytics ------------------------------------------------------------

    public function test_revenue_is_shown_per_currency(): void
    {
        $resource = $this->resource();
        foreach ([['USD', 10], ['USD', 5], ['NGN', 2500]] as [$currency, $amount]) {
            LibraryResourcePurchase::create(['tenant_id' => $this->tenant->id, 'resource_id' => $resource->id, 'user_id' => $this->member->id, 'gateway' => 'bank_transfer', 'amount' => $amount, 'currency' => $currency, 'status' => 'paid', 'reference' => (string) Str::uuid()]);
        }

        $this->actingAs($this->owner)->get('/t/acme/library/manage/analytics')
            ->assertOk()->assertSee('USD 15.00')->assertSee('NGN 2,500.00')->assertDontSee('2,515.00');
    }

    // --- Ratings and favourites -----------------------------------------------

    public function test_only_people_who_can_use_a_resource_can_rate_it(): void
    {
        $open = $this->resource(['slug' => 'open']);
        $lent = $this->resource(['slug' => 'lent', 'requires_checkout' => true, 'total_copies' => 1]);

        $this->actingAs($this->member)->post('/t/acme/library/resources/open/ratings', ['stars' => 4])->assertRedirect();
        $this->actingAs($this->member)->post('/t/acme/library/resources/lent/ratings', ['stars' => 4])->assertForbidden();
        $this->actingAs($this->member)->get('/t/acme/library/resources/lent')->assertDontSee('Rate this resource');

        // A past loan is enough.
        LibraryCheckout::create(['tenant_id' => $this->tenant->id, 'resource_id' => $lent->id, 'user_id' => $this->member->id, 'checked_out_at' => now()->subWeek(), 'due_at' => now()->subDay(), 'returned_at' => now()->subDay()]);
        $this->actingAs($this->member)->post('/t/acme/library/resources/lent/ratings', ['stars' => 5])->assertRedirect();
        $this->assertSame(2, $open->ratings()->count() + $lent->ratings()->count());
    }

    public function test_unpublished_resources_cannot_be_rated_or_favourited(): void
    {
        $resource = $this->resource(['slug' => 'draft', 'is_published' => false]);

        $this->actingAs($this->member)->post('/t/acme/library/resources/draft/ratings', ['stars' => 4])->assertForbidden();
        $this->actingAs($this->member)->post('/t/acme/library/resources/draft/favorite')->assertNotFound();
        $this->assertSame(0, $resource->favorites()->count());
    }

    public function test_favourites_list_hides_resources_that_were_unpublished(): void
    {
        $resource = $this->resource(['title' => 'Soon hidden', 'slug' => 'soon-hidden']);
        $this->actingAs($this->member)->post('/t/acme/library/resources/soon-hidden/favorite');
        $this->actingAs($this->member)->get('/t/acme/library/my-favorites')->assertSee('Soon hidden');

        $resource->update(['is_published' => false]);

        $this->actingAs($this->member)->get('/t/acme/library/my-favorites')->assertDontSee('Soon hidden');
    }

    public function test_star_ratings_have_accessible_names(): void
    {
        $this->resource(['slug' => 'open']);

        $this->actingAs($this->member)->get('/t/acme/library/resources/open')
            ->assertSee('<span class="sr-only">1 star</span>', false)->assertSee('<span class="sr-only">5 stars</span>', false);
    }

    // --- Uploads ----------------------------------------------------------------

    public function test_a_wrong_file_type_is_reported_on_the_form(): void
    {
        Storage::fake('local');
        $this->resource(['slug' => 'open']);

        $this->actingAs($this->owner)->from('/t/acme/library/manage/resources/open/edit')
            ->post('/t/acme/library/manage/resources/open/files', [
                'title' => 'Wrong', 'format' => 'pdf', 'access_level' => 'secure',
                'file' => UploadedFile::fake()->create('notes.txt', 5, 'text/plain'),
            ])
            ->assertRedirect('/t/acme/library/manage/resources/open/edit')
            ->assertSessionHasErrors('file');

        $this->actingAs($this->owner)->withSession(['errors' => session('errors')])
            ->get('/t/acme/library/manage/resources/open/edit')->assertOk();
    }

    // --- Payment keys -----------------------------------------------------------

    public function test_saved_secrets_never_reach_the_page_and_blank_keeps_them(): void
    {
        $url = '/t/acme/library/manage/payment-gateways';
        $this->actingAs($this->owner)->put("{$url}/paystack", ['is_enabled' => '1', 'public_key' => 'pk_live_visible', 'secret_key' => 'sk_live_TOPSECRET', 'webhook_secret' => 'whsec_HIDDEN']);

        $this->actingAs($this->owner)->get($url)
            ->assertOk()->assertSee('pk_live_visible')->assertDontSee('sk_live_TOPSECRET')->assertDontSee('whsec_HIDDEN')
            ->assertSee('Saved — leave blank to keep it');

        // Toggling or editing the public key keeps the secrets.
        $this->actingAs($this->owner)->put("{$url}/paystack", ['public_key' => 'pk_live_new', 'secret_key' => '', 'webhook_secret' => '']);
        $credentials = LibraryPaymentGatewayCredential::withoutGlobalScopes()->where('gateway', 'paystack')->first()->credentials;
        $this->assertSame(['public_key' => 'pk_live_new', 'secret_key' => 'sk_live_TOPSECRET', 'webhook_secret' => 'whsec_HIDDEN'], $credentials);

        // Typing a new one replaces it; the checkbox removes them.
        $this->actingAs($this->owner)->put("{$url}/paystack", ['public_key' => 'pk_live_new', 'secret_key' => 'sk_live_ROTATED']);
        $this->assertSame('sk_live_ROTATED', LibraryPaymentGatewayCredential::withoutGlobalScopes()->first()->credentials['secret_key']);
        $this->actingAs($this->owner)->put("{$url}/paystack", ['public_key' => 'pk_live_new', 'clear_secrets' => '1']);
        $this->assertSame(['public_key' => 'pk_live_new'], LibraryPaymentGatewayCredential::withoutGlobalScopes()->first()->credentials);
    }

    public function test_the_lms_settings_behave_the_same(): void
    {
        $this->actingAs($this->owner)->put('/t/acme/lms/manage/payment-gateways/stripe', ['is_enabled' => '1', 'secret_key' => 'sk_live_LMSSECRET']);
        $this->actingAs($this->owner)->get('/t/acme/lms/manage/payment-gateways')->assertOk()->assertDontSee('sk_live_LMSSECRET');

        $this->actingAs($this->owner)->put('/t/acme/lms/manage/payment-gateways/stripe', ['is_enabled' => '0']);
        $this->assertSame('sk_live_LMSSECRET', CoursePaymentGatewayCredential::withoutGlobalScopes()->first()->credentials['secret_key']);
    }

    // --- Readers and languages --------------------------------------------------

    public function test_the_readers_load_their_libraries_from_this_site(): void
    {
        Storage::fake('local');
        $resource = $this->resource(['slug' => 'open']);
        foreach (['pdf', 'epub'] as $format) {
            Storage::disk('local')->put("library-files/1/b.{$format}", 'x');
            $resource->files()->create(['tenant_id' => $this->tenant->id, 'title' => $format, 'format' => $format, 'access_level' => 'secure', 'disk_path' => "library-files/1/b.{$format}", 'position' => 0]);
        }
        $pdf = \Elibrary\Library\Models\LibraryResourceFile::withoutGlobalScopes()->where('format', 'pdf')->first();
        $epub = \Elibrary\Library\Models\LibraryResourceFile::withoutGlobalScopes()->where('format', 'epub')->first();

        $this->actingAs($this->member)->get("/t/acme/library/resources/open/files/{$epub->id}/read")
            ->assertOk()->assertSee('/vendor/epubjs-0.3.93/epub.min.js')->assertDontSee('cdn.jsdelivr.net/npm/epubjs');
        $this->actingAs($this->member)->get("/t/acme/library/resources/open/files/{$pdf->id}/read")->assertOk();

        $this->assertFileExists(public_path('vendor/pdfjs-6.1.200/pdf.worker.min.js'));
        $this->assertStringNotContainsString('cdn.jsdelivr', file_get_contents(public_path('js/library-pdf-reader.js')));
    }

    public function test_the_library_speaks_the_learners_language(): void
    {
        $this->tenant->update(['locale' => 'fr']);
        $this->resource(['slug' => 'open', 'author' => 'A. Okafor']);

        $this->actingAs($this->member)->get('/t/acme/library')->assertOk()->assertSee('Trouvez votre prochaine lecture')->assertSee('Mes favoris');
        $this->actingAs($this->member)->get('/t/acme/library/resources/open')->assertOk()->assertSee('par <span', false)->assertSee('Notez cette ressource');
    }
}
