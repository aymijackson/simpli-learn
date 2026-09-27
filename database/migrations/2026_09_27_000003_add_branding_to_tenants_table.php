<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('domain');
            // #rrggbb; the whole brand palette is derived from it.
            $table->string('brand_color', 7)->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'brand_color']);
        });
    }
};
