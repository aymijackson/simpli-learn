<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_resources', function (Blueprint $table) {
            $table->boolean('requires_checkout')->default(false)->after('is_published');
        });

        Schema::create('library_resource_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('library_resources')->cascadeOnDelete();
            $table->string('title');
            $table->string('format');
            $table->string('access_level')->default('secure');
            $table->string('disk_path');
            $table->string('mime_type')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_resource_files');

        Schema::table('library_resources', function (Blueprint $table) {
            $table->dropColumn('requires_checkout');
        });
    }
};
