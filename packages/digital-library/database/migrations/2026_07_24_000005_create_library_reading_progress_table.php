<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_file_id')->constrained('library_resource_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // A page number (PDF) or an EPUB CFI location string — the two
            // formats' position representations are fundamentally different,
            // neither needs to be queried structurally, just stored/restored.
            $table->string('position', 512);
            $table->timestamps();

            $table->unique(['resource_file_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_reading_progress');
    }
};
