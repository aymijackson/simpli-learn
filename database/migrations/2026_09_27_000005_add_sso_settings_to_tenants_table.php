<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Sign-in providers the owner switched on, e.g. ["google","microsoft"].
            $table->json('sso_providers')->nullable()->after('brand_color');
            // Email domains whose people may join on their first SSO sign-in.
            $table->json('sso_domains')->nullable()->after('sso_providers');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['sso_providers', 'sso_domains']);
        });
    }
};
