<?php

namespace Database\Seeders;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            ['name' => 'Acme University', 'slug' => 'acme', 'status' => TenantStatus::Active, 'modules' => [Module::Lms, Module::Cbt, Module::Library]],
            ['name' => 'Bright CBT Testing', 'slug' => 'brightcbt', 'status' => TenantStatus::Active, 'modules' => [Module::Cbt]],
            ['name' => 'City Public Library', 'slug' => 'citylibrary', 'status' => TenantStatus::Active, 'modules' => [Module::Library]],
            ['name' => 'Riverside School', 'slug' => 'riverside', 'status' => TenantStatus::Pending, 'modules' => [Module::Lms, Module::Cbt]],
        ];

        foreach ($tenants as $definition) {
            $tenant = Tenant::create([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'status' => $definition['status'],
            ]);

            foreach ($definition['modules'] as $module) {
                $tenant->tenantModules()->create([
                    'module' => $module,
                    'is_enabled' => true,
                ]);
            }

            User::forceCreate([
                'tenant_id' => $tenant->id,
                'role' => UserRole::Owner,
                'name' => "{$definition['name']} Admin",
                'email' => "admin@{$definition['slug']}.test",
                'email_verified_at' => now(),
                'password' => 'password',
            ]);
        }
    }
}
