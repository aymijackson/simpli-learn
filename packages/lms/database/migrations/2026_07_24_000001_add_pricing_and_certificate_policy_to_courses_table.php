<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('pricing_policy')->default('free')->after('is_published');
            $table->decimal('price', 10, 2)->nullable()->after('pricing_policy');
            $table->char('currency', 3)->nullable()->after('price');
            $table->string('certificate_policy')->default('none')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['pricing_policy', 'price', 'currency', 'certificate_policy']);
        });
    }
};
