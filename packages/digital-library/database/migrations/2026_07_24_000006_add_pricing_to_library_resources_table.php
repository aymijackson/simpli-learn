<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_resources', function (Blueprint $table) {
            $table->string('pricing_policy')->default('free')->after('checkout_duration_days');
            $table->decimal('price', 10, 2)->nullable()->after('pricing_policy');
            $table->char('currency', 3)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('library_resources', function (Blueprint $table) {
            $table->dropColumn(['pricing_policy', 'price', 'currency']);
        });
    }
};
