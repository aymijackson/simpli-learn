<?php

namespace Tests\Unit;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_context_only_sees_users_without_a_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);

        $central = User::factory()->create(['tenant_id' => null, 'email' => 'central@example.test']);
        User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'user@acme.test']);

        $visible = User::all();

        $this->assertCount(1, $visible);
        $this->assertTrue($visible->first()->is($central));
    }

    public function test_tenant_context_only_sees_that_tenants_users(): void
    {
        $acme = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $other = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);

        $acmeUser = User::factory()->create(['tenant_id' => $acme->id, 'email' => 'user@acme.test']);
        User::factory()->create(['tenant_id' => $other->id, 'email' => 'user@other.test']);
        User::factory()->create(['tenant_id' => null, 'email' => 'central@example.test']);

        app(Tenancy::class)->set($acme);

        $visible = User::all();

        $this->assertCount(1, $visible);
        $this->assertTrue($visible->first()->is($acmeUser));
    }
}
