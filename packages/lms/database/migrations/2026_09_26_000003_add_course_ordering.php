<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Lessons open one at a time, each after the previous is completed.
            $table->boolean('sequential_lessons')->default(false)->after('assessment_mode');
        });

        // Courses that must be completed (and passed) before this one opens.
        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_course_id')->constrained('courses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'prerequisite_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_prerequisites');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('sequential_lessons');
        });
    }
};
