<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The workspace's default interface language, and each person's own choice.
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('brand_color');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
