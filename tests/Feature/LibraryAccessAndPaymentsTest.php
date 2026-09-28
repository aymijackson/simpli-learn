<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Checkouts\LibraryCheckoutService;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourceFile;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LibraryAccessAndPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->member = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function resource(array $attributes = []): LibraryResource
    {
        return LibraryResource::create(array_merge([
            'tenant_id' => $this->tenant->id, 'title' => 'Rare Book', 'slug' => 'rare-book', 'category' => 'Fiction',
            'is_published' => true, 'requires_checkout' => false,
        ], $attributes));
    }

    private function paidResource(array $attributes = []): LibraryResource
    {
        return $this->resource(array_merge(['pricing_policy' => 'paid', 'price' => 10, 'currency' => 'USD'], $attributes));
    }

    private function file(LibraryResource $resource, string $level = 'secure', string $format = 'pdf'): LibraryResourceFile
    {
        $path = "library-files/{$this->tenant->id}/".Str::random(8).'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        return $resource->files()->create([
            'tenant_id' => $this->tenant->id, 'title' => 'Full text', 'format' => $format, 'access_level' => $level,
            'disk_path' => $path, 'mime_type' => 'application/pdf', 'original_filename' => 'book.pdf', 'position' => 0,
        ]);
    }

    private function purchase(LibraryResource $resource, string $status, string $gateway = 'paystack', array $attributes = []): LibraryResourcePurchase
    {
        return LibraryResourcePurchase::create(array_merge([
            'tenant_id' => $this->tenant->id, 'resource_id' => $resource->id, 'user_id' => $this->member->id,
            'gateway' => $gateway, 'amount' => 10, 'currency' => 'USD', 'status' => $status, 'reference' => (string) Str::uuid(),
        ], $attributes));
    }

    private function enablePaystack(): void
    {
        $this->actingAs($this->owner)->put('/t/acme/library/manage/payment-gateways/paystack', ['is_enabled' => '1', 'secret_key' => 'sk_test']);
    }

    private function stream(User $user, LibraryResourceFile $file): int
    {
        return $this->actingAs($user)->get("/t/acme/library/resources/rare-book/files/{$file->id}/stream")->baseResponse->getStatusCode();
    }

    // --- Paid resources ---------------------------------------------------

    public function test_a_paid_resource_that_isnt_lent_out_still_has_to_be_bought(): void
    {
        $resource = $this->paidResource(['external_url' => 'https://example.com/secret-book']);
        $file = $this->file($resource);

        $this->assertSame(403, $this->stream($this->member, $file));
        $this->actingAs($this->member)->get('/t/acme/library/resources/rare-book')
            ->assertOk()
            ->assertSee('Buy for 10.00 USD')
            ->assertSee('Buy this resource to access its files.')
            ->assertDontSee('https://example.com/secret-book');

        $this->purchase($resource, 'paid');

        $this->assertSame(200, $this->stream($this->member, $file));
        $this->actingAs($this->member)->get('/t/acme/library/resources/rare-book')
            ->assertSee('https://example.com/secret-book')->assertSee('You own this resource.')->assertDontSee('Buy for');
    }

    public function test_owners_can_open_paid_resources_without_buying(): void
    {
        $file = $this->file($this->paidResource(['requires_checkout' => true, 'total_copies' => 1]));

        $this->assertSame(200, $this->stream($this->owner, $file));
    }

    public function test_the_purchase_page_is_only_for_published_priced_paid_resources(): void
    {
        $this->resource(['slug' => 'free-book']);
        $this->paidResource(['slug' => 'draft', 'is_published' => false]);
        LibraryResource::create(['tenant_id' => $this->tenant->id, 'title' => 'No price', 'slug' => 'no-price', 'category' => 'x', 'is_published' => true, 'pricing_policy' => 'paid']);
        $this->paidResource();

        foreach (['free-book', 'draft', 'no-price'] as $slug) {
            $this->actingAs($this->member)->get("/t/acme/library/resources/{$slug}/purchase")->assertNotFound();
        }
        $this->actingAs($this->member)->get('/t/acme/library/resources/rare-book/purchase')->assertOk();
    }

    public function test_a_paid_resource_needs_a_price_and_currency(): void
    {
        $this->actingAs($this->owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Book', 'slug' => 'book', 'category' => 'x', 'pricing_policy' => 'paid',
        ])->assertSessionHasErrors(['price', 'currency']);

        $this->actingAs($this->owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Book', 'slug' => 'book', 'category' => 'x', 'pricing_policy' => 'paid', 'price' => '0', 'currency' => 'usd',
        ])->assertSessionHasErrors('price');

        $this->actingAs($this->owner)->post('/t/acme/library/manage/resources', [
            'title' => 'Book', 'slug' => 'book', 'category' => 'x', 'pricing_policy' => 'paid', 'price' => '5', 'currency' => 'usd',
        ])->assertSessionHasNoErrors();
        $this->assertSame('USD', LibraryResource::where('slug', 'book')->first()->currency);
    }

    // --- Files --------------------------------------------------------------

    public function test_open_files_are_served_by_the_app_and_only_while_access_lasts(): void
    {
        $resource = $this->resource(['requires_checkout' => true, 'total_copies' => 1]);
        $file = $this->file($resource, 'open');
        $url = "/t/acme/library/resources/rare-book/files/{$file->id}/download";

        $this->actingAs($this->member)->get($url)->assertForbidden();

        app(\App\Support\Tenancy\Tenancy::class)->set($this->tenant);
        $loan = app(LibraryCheckoutService::class)->borrow($resource, $this->member);
        $response = $this->actingAs($this->member)->get($url);
        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        app(LibraryCheckoutService::class)->return($loan);
        $this->actingAs($this->member)->get($url)->assertForbidden();
    }

    public function test_signed_out_visitors_cannot_download(): void
    {
        $file = $this->file($this->resource(), 'open');

        $this->get("/t/acme/library/resources/rare-book/files/{$file->id}/download")->assertRedirect();
    }

    public function test_secure_files_can_be_viewed_but_not_downloaded(): void
    {
        $file = $this->file($this->resource(), 'secure');

        $this->assertSame(200, $this->stream($this->member, $file));
        $this->actingAs($this->member)->get("/t/acme/library/resources/rare-book/files/{$file->id}/download")->assertNotFound();
    }

    public function test_open_pdfs_open_in_the_reader(): void
    {
        $file = $this->file($this->resource(), 'open');

        $this->actingAs($this->member)->get("/t/acme/library/resources/rare-book/files/{$file->id}/read")->assertOk();
        $this->assertSame(200, $this->stream($this->member, $file));
    }

    public function test_new_open_uploads_go_to_private_storage(): void
    {
        $this->resource();

        $this->actingAs($this->owner)->post('/t/acme/library/manage/resources/rare-book/files', [
            'title' => 'Notes', 'format' => 'text', 'access_level' => 'open',
            'file' => \Illuminate\Http\UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertRedirect();

        $path = LibraryResourceFile::withoutGlobalScopes()->first()->disk_path;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_the_migration_moves_existing_open_files_off_the_public_disk(): void
    {
        $resource = $this->resource();
        Storage::disk('public')->put('library-files/1/old.pdf', 'old bytes');
        $resource->files()->create(['tenant_id' => $this->tenant->id, 'title' => 'Old', 'format' => 'pdf', 'access_level' => 'open', 'disk_path' => 'library-files/1/old.pdf', 'position' => 0]);

        $migration = require base_path('packages/digital-library/database/migrations/2026_09_28_000001_move_open_library_files_to_private_storage.php');
        $migration->up();
        $migration->up(); // re-running is harmless

        Storage::disk('public')->assertMissing('library-files/1/old.pdf');
        $this->assertSame('old bytes', Storage::disk('local')->get('library-files/1/old.pdf'));
    }

    // --- Deleting -----------------------------------------------------------

    public function test_a_resource_with_purchases_cannot_be_deleted(): void
    {
        $resource = $this->paidResource();
        $this->purchase($resource, 'paid');

        $this->actingAs($this->owner)->from('/t/acme/library/manage/resources')
            ->delete('/t/acme/library/manage/resources/rare-book')
            ->assertSessionHasErrors('resource');

        $this->assertNotNull($resource->fresh());
        $this->assertSame(1, LibraryResourcePurchase::withoutGlobalScopes()->count());
        $this->actingAs($this->owner)->get('/t/acme/library/manage/resources')->assertOk();
    }

    public function test_deleting_a_resource_removes_its_stored_files(): void
    {
        $resource = $this->resource();
        $file = $this->file($resource, 'open');
        $this->purchase($resource, 'cancelled');

        $this->actingAs($this->owner)->delete('/t/acme/library/manage/resources/rare-book')->assertRedirect();

        $this->assertNull($resource->fresh());
        Storage::disk('local')->assertMissing($file->disk_path);
    }

    // --- Payment confirmation -------------------------------------------------

    public function test_a_webhook_for_the_wrong_amount_does_not_grant_access(): void
    {
        $this->enablePaystack();
        $purchase = $this->purchase($this->paidResource(), 'pending');
        Http::fake(["api.paystack.co/transaction/verify/{$purchase->reference}" => Http::response([
            'status' => true, 'data' => ['status' => 'success', 'reference' => $purchase->reference, 'amount' => 100, 'currency' => 'USD'],
        ])]);

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $purchase->reference]]);
        $this->call('POST', '/webhooks/library/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => hash_hmac('sha512', $payload, 'sk_test'), 'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertNoContent();

        $this->assertSame('pending', $purchase->fresh()->status->value);
    }

    public function test_a_webhook_in_the_wrong_currency_does_not_grant_access(): void
    {
        $this->enablePaystack();
        $purchase = $this->purchase($this->paidResource(), 'pending');
        Http::fake(["api.paystack.co/transaction/verify/{$purchase->reference}" => Http::response([
            'status' => true, 'data' => ['status' => 'success', 'reference' => $purchase->reference, 'amount' => 1000, 'currency' => 'NGN'],
        ])]);

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => $purchase->reference]]);
        $this->call('POST', '/webhooks/library/paystack', [], [], [], [
            'HTTP_x-paystack-signature' => hash_hmac('sha512', $payload, 'sk_test'), 'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertSame('pending', $purchase->fresh()->status->value);
    }

    public function test_returning_from_checkout_confirms_the_payment_without_the_webhook(): void
    {
        $this->enablePaystack();
        $resource = $this->paidResource();
        $file = $this->file($resource);
        $purchase = $this->purchase($resource, 'pending');
        Http::fake(["api.paystack.co/transaction/verify/{$purchase->reference}" => Http::response([
            'status' => true, 'data' => ['status' => 'success', 'reference' => $purchase->reference, 'amount' => 1000, 'currency' => 'USD'],
        ])]);

        $this->actingAs($this->member)->get("/t/acme/library/resources/rare-book?reference={$purchase->reference}")
            ->assertOk()->assertSee('Payment received')->assertDontSee('Buy for');

        $this->assertSame('paid', $purchase->fresh()->status->value);
        $this->assertSame(200, $this->stream($this->member, $file));
    }

    public function test_the_return_finds_an_older_paid_attempt(): void
    {
        $this->enablePaystack();
        $resource = $this->paidResource();
        $paid = $this->purchase($resource, 'pending');
        $abandoned = $this->purchase($resource, 'pending');
        Http::fake([
            "api.paystack.co/transaction/verify/{$abandoned->reference}" => Http::response(['status' => true, 'data' => ['status' => 'abandoned']]),
            "api.paystack.co/transaction/verify/{$paid->reference}" => Http::response([
                'status' => true, 'data' => ['status' => 'success', 'reference' => $paid->reference, 'amount' => 1000, 'currency' => 'USD'],
            ]),
        ]);

        $this->actingAs($this->member)->get("/t/acme/library/resources/rare-book?reference={$paid->reference}")->assertOk();

        $this->assertSame('paid', $paid->fresh()->status->value);
        $this->assertSame('pending', $abandoned->fresh()->status->value);
    }

    public function test_an_unconfirmed_return_says_so_and_grants_nothing(): void
    {
        $this->enablePaystack();
        $resource = $this->paidResource();
        $purchase = $this->purchase($resource, 'pending');
        Http::fake(["api.paystack.co/transaction/verify/{$purchase->reference}" => Http::response(['status' => true, 'data' => ['status' => 'ongoing']])]);

        $this->actingAs($this->member)->get("/t/acme/library/resources/rare-book?reference={$purchase->reference}")
            ->assertOk()->assertSee('still waiting for the payment provider')->assertSee('Buy for');

        $this->assertSame('pending', $purchase->fresh()->status->value);
    }

    public function test_plain_visits_do_not_call_the_gateway(): void
    {
        $this->enablePaystack();
        $this->purchase($this->paidResource(), 'pending');
        Http::fake();

        $this->actingAs($this->member)->get('/t/acme/library/resources/rare-book')->assertOk();

        Http::assertNothingSent();
    }
}
