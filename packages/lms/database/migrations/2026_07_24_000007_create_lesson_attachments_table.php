<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type');
            // Shaped to admit a future third tier (e.g. DRM) without a schema
            // change, but only 'open' and 'secure' are implemented.
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
        Schema::dropIfExists('lesson_attachments');
    }
};
