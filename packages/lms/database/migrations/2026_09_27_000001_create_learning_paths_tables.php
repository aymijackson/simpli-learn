<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(false);
            // Each course opens only after the one before it is passed.
            $table->boolean('is_sequential')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('learning_path_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['learning_path_id', 'course_id']);
        });

        // Prerequisites a sequential path created, so the path can manage them
        // without touching ones an owner set on the course itself.
        Schema::table('course_prerequisites', function (Blueprint $table) {
            $table->foreignId('learning_path_id')->nullable()->after('prerequisite_course_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_prerequisites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_path_id');
        });
        Schema::dropIfExists('learning_path_courses');
        Schema::dropIfExists('learning_paths');
    }
};
