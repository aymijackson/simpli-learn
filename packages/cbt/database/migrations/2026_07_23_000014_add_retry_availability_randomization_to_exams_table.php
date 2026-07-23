<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('allow_retakes')->default(true)->after('pass_percentage');
            $table->unsignedInteger('max_attempts')->nullable()->after('allow_retakes');
            $table->timestamp('available_from')->nullable()->after('max_attempts');
            $table->timestamp('available_until')->nullable()->after('available_from');
            $table->boolean('randomize_questions')->default(false)->after('available_until');
            $table->unsignedInteger('questions_per_attempt')->nullable()->after('randomize_questions');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'allow_retakes', 'max_attempts', 'available_from', 'available_until',
                'randomize_questions', 'questions_per_attempt',
            ]);
        });
    }
};
