<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->text('instructions')->nullable()->after('description');
            $table->string('navigation_mode')->default('all_at_once')->after('enforce_time_limit');
            $table->boolean('allow_backward_navigation')->default(true)->after('navigation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['instructions', 'navigation_mode', 'allow_backward_navigation']);
        });
    }
};
