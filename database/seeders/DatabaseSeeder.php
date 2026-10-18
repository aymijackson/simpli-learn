<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo data, including a test@example.com / "password" login. Never on a
        // live site: there, seed a specific class with --class=.
        if (app()->environment('production')) {
            $this->command?->error('DatabaseSeeder creates demo data and a test login, so it does not run in production.');
            $this->command?->line('  Seed a specific class instead, e.g. php artisan db:seed --class=ThekanServicesSeeder --force');

            return;
        }

        // No factories here: Faker is a dev dependency, and production installs
        // use `composer install --no-dev`.
        User::forceCreate([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        $this->call([
            MarketingPageSeeder::class,
            TenantSeeder::class,
            LmsSeeder::class,
            CbtSeeder::class,
            LibrarySeeder::class,
        ]);
    }
}
