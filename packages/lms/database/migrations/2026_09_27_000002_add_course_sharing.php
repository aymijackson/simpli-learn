<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Open to learners in every workspace (set by the owner or a platform admin).
            $table->boolean('is_public')->default(false)->after('is_published');
            $table->index(['is_public', 'is_published']);
        });

        // Workspaces a platform admin has shared a course with.
        Schema::create('course_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'tenant_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_shares');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['is_public', 'is_published']);
            $table->dropColumn('is_public');
        });
    }
};
