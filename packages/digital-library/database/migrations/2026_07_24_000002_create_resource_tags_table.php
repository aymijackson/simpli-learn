<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('library_resource_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('library_resources')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('resource_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['resource_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_resource_tag');
        Schema::dropIfExists('resource_tags');
    }
};
