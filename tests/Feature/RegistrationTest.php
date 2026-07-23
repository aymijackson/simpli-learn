<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'organization_name' => 'Riverside School',
            'slug' => 'riverside',
            'name' => 'Jamie Owner',
            'email' => 'jamie@riverside.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'modules' => ['lms', 'cbt'],
        ], $overrides);
    }

    public function test_signup_creates_a_pending_tenant_with_the_chosen_modules(): void
    {
        $response = $this->post('/signup', $this->validPayload());

        $response->assertRedirect(route('signup.pending'));

        $tenant = Tenant::where('slug', 'riverside')->first();

        $this->assertNotNull($tenant);
        $this->assertTrue($tenant->isPending());
        $this->assertTrue($tenant->hasModule('lms'));
        $this->assertTrue($tenant->hasModule('cbt'));
        $this->assertFalse($tenant->hasModule('library'));

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'jamie@riverside.test',
        ]);
    }

    public function test_a_pending_tenants_user_cannot_log_in_before_approval(): void
    {
        $this->post('/signup', $this->validPayload());

        $response = $this->post('/t/riverside/login', [
            'email' => 'jamie@riverside.test',
            'password' => 'password',
        ]);

        // The tenant itself 404s while pending, so the login attempt never
        // even reaches the credential check.
        $response->assertNotFound();
        $this->assertGuest();
    }

    public function test_signup_requires_at_least_one_module(): void
    {
        $response = $this->post('/signup', $this->validPayload(['modules' => []]));

        $response->assertSessionHasErrors('modules');
        $this->assertDatabaseMissing('tenants', ['slug' => 'riverside']);
    }

    public function test_signup_rejects_a_slug_already_in_use(): void
    {
        Tenant::create(['name' => 'Existing', 'slug' => 'riverside', 'status' => TenantStatus::Active]);

        $response = $this->post('/signup', $this->validPayload());

        $response->assertSessionHasErrors('slug');
    }

    public function test_an_authenticated_central_user_cannot_reach_the_signup_form(): void
    {
        $admin = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)->get('/signup')->assertRedirect();
    }
}
