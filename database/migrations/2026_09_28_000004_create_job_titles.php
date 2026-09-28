<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job titles, per workspace. People have one; courses and library resources
 * can be recommended for several. `key` is the name lower-cased with spaces
 * collapsed, so "Senior  Accountant" and "senior accountant" are one title.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('key', 120);
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('job_title_id')->nullable()->after('role')->constrained('job_titles')->nullOnDelete();
        });

        Schema::create('course_job_title', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_title_id')->constrained()->cascadeOnDelete();
            $table->primary(['course_id', 'job_title_id']);
        });

        Schema::create('library_resource_job_title', function (Blueprint $table) {
            $table->foreignId('resource_id')->constrained('library_resources')->cascadeOnDelete();
            $table->foreignId('job_title_id')->constrained()->cascadeOnDelete();
            $table->primary(['resource_id', 'job_title_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_resource_job_title');
        Schema::dropIfExists('course_job_title');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_title_id');
        });
        Schema::dropIfExists('job_titles');
    }
};
