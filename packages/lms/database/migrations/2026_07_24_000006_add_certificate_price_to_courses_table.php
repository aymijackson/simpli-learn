<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Separate from `price`/`currency` (enrollment) — a free-to-enroll
            // course can still charge specifically for its certificate.
            $table->decimal('certificate_price', 10, 2)->nullable()->after('certificate_policy');
            $table->char('certificate_currency', 3)->nullable()->after('certificate_price');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['certificate_price', 'certificate_currency']);
        });
    }
};
