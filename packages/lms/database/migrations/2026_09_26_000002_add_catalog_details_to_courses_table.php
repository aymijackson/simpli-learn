<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('subtitle')->nullable()->after('title');
            $table->string('cover_image_path')->nullable()->after('description');
            $table->string('category', 100)->nullable()->after('cover_image_path');
            $table->string('level', 20)->nullable()->after('category');
            $table->unsignedInteger('duration_minutes')->nullable()->after('level');
            $table->string('instructor_name')->nullable()->after('duration_minutes');
            $table->text('instructor_bio')->nullable()->after('instructor_name');
            $table->json('outcomes')->nullable()->after('instructor_bio');

            $table->index(['tenant_id', 'category']);
        });

        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_reviews');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'category']);
            $table->dropColumn(['subtitle', 'cover_image_path', 'category', 'level', 'duration_minutes', 'instructor_name', 'instructor_bio', 'outcomes']);
        });
    }
};
