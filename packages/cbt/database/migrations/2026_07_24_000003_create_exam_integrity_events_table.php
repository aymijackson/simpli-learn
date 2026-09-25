<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_integrity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('event_type');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['exam_attempt_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_integrity_events');
    }
};
