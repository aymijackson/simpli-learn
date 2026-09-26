<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryFavorite;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CoursePurchase;
use Elibrary\Lms\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalDataTest extends TestCase
{
    use RefreshDatabase;

    /** A workspace with an owner and a member who has learning, library and payment records. */
    private function scenario(): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        foreach ([Module::Lms, Module::Library] as $module) {
            $tenant->tenantModules()->create(['module' => $module, 'is_enabled' => true]);
        }
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner, 'name' => 'Olu Owner']);
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member, 'name' => 'Jordan Member', 'email' => 'jordan@acme.test']);

        $course = Course::create(['tenant_id' => $tenant->id, 'title' => 'Data Security', 'slug' => 'data-security', 'is_published' => true]);
        Enrollment::create(['tenant_id' => $tenant->id, 'course_id' => $course->id, 'user_id' => $member->id]);
        CoursePurchase::create([
            'tenant_id' => $tenant->id, 'course_id' => $course->id, 'user_id' => $member->id, 'purchase_type' => 'enrollment',
            'gateway' => 'bank_transfer', 'amount' => 5000, 'currency' => 'NGN', 'status' => 'paid', 'reference' => 'REF-123',
        ]);

        $resource = LibraryResource::create([
            'tenant_id' => $tenant->id, 'title' => 'Rare Book', 'slug' => 'rare-book', 'category' => 'Fiction',
            'is_published' => true, 'requires_checkout' => true, 'total_copies' => 1, 'checkout_duration_days' => 14,
        ]);
        LibraryFavorite::create(['tenant_id' => $tenant->id, 'resource_id' => $resource->id, 'user_id' => $member->id]);
        LibraryCheckout::create(['tenant_id' => $tenant->id, 'resource_id' => $resource->id, 'user_id' => $member->id, 'checked_out_at' => now(), 'due_at' => now()->addDays(14)]);

        ActivityLog::record('team.member_added', 'Added Jordan Member (jordan@acme.test) as Member', $member, actor: $owner, tenantId: $tenant->id);

        return [$tenant, $owner, $member];
    }

    public function test_an_owner_can_download_a_members_data(): void
    {
        [, $owner] = $this->scenario();
        $member = User::where('email', 'jordan@acme.test')->withoutGlobalScopes()->first();

        $response = $this->actingAs($owner)->get("/t/acme/team/{$member->id}/data");

        $response->assertOk();
        $this->assertStringContainsString('personal-data-jordan-member-', $response->headers->get('content-disposition'));

        $data = json_decode($response->streamedContent(), true);
        $this->assertSame('jordan@acme.test', $data['account']['email']);
        $this->assertCount(1, $data['records']['course_enrollments']);
        $this->assertSame('REF-123', $data['records']['course_purchases'][0]['reference']);
        $this->assertCount(1, $data['records']['library_favorites']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'data.exported', 'user_id' => $owner->id]);
    }

    public function test_anyone_can_download_their_own_data(): void
    {
        [, , $member] = $this->scenario();

        $response = $this->actingAs($member)->get('/t/acme/profile/export');

        $response->assertOk();
        $this->assertSame('Jordan Member', json_decode($response->streamedContent(), true)['account']['name']);
    }

    public function test_members_cannot_export_other_peoples_data(): void
    {
        [, $owner, $member] = $this->scenario();

        $this->actingAs($member)->get("/t/acme/team/{$owner->id}/data")->assertForbidden();
    }

    public function test_erasing_anonymises_the_member_but_keeps_financial_records(): void
    {
        [$tenant, $owner, $member] = $this->scenario();

        $this->actingAs($owner)->post("/t/acme/team/{$member->id}/data/erase")->assertRedirect('/t/acme/team');

        $erased = User::withoutGlobalScopes()->find($member->id);
        $this->assertSame("Erased user #{$member->id}", $erased->name);
        $this->assertStringEndsWith('@erased.invalid', $erased->email);

        // Personal extras gone; financial and learning records kept against the anonymous account.
        $this->assertSame(0, LibraryFavorite::withoutGlobalScopes()->where('user_id', $member->id)->count());
        $this->assertSame(1, CoursePurchase::withoutGlobalScopes()->where('user_id', $member->id)->count());
        $this->assertSame(1, Enrollment::withoutGlobalScopes()->where('user_id', $member->id)->count());
        $this->assertNotNull(LibraryCheckout::withoutGlobalScopes()->where('user_id', $member->id)->first()->returned_at);

        // Name and email scrubbed from the activity trail.
        $this->assertSame(0, ActivityLog::where('description', 'like', '%jordan@acme.test%')->count());
        $this->assertSame(0, ActivityLog::where('description', 'like', '%Jordan Member%')->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'data.erased', 'tenant_id' => $tenant->id]);

        // They can no longer sign in.
        auth()->logout();
        $this->post('/t/acme/login', ['email' => 'jordan@acme.test', 'password' => 'password'])->assertSessionHasErrors('email');
    }

    public function test_an_owner_cannot_erase_themselves_or_the_last_owner(): void
    {
        [, $owner] = $this->scenario();

        $this->actingAs($owner)->post("/t/acme/team/{$owner->id}/data/erase")
            ->assertSessionHasErrors(['member' => 'You cannot erase your own account from here.']);

        $this->assertSame('Olu Owner', $owner->fresh()->name);
    }
}
