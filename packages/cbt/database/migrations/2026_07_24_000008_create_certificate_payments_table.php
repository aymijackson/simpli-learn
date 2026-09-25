<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('certificate_id')->nullable()->constrained('certificates')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3);
            $table->string('status')->default('pending');
            $table->string('reference')->unique();
            $table->string('gateway_reference')->nullable();
            // Snapshotted at creation time, never recomputed live, so a later
            // change to the platform mode or a tenant's split never rewrites
            // historical payment records.
            $table->string('collected_by');
            $table->decimal('platform_fee_amount', 10, 2)->nullable();
            $table->text('platform_fee_note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_payments');
    }
};
