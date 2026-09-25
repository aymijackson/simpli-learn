<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('certificate_policy')->default('inherit')->after('pass_percentage');
            $table->decimal('certificate_price', 10, 2)->nullable()->after('certificate_policy');
            $table->char('certificate_currency', 3)->nullable()->after('certificate_price');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['certificate_policy', 'certificate_price', 'certificate_currency']);
        });
    }
};
