<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_payment_gateway_credentials', function (Blueprint $table) {
            $table->id();
            // Tenant-managed only in this pass — no platform-wide override for
            // course payments, so unlike CBT's certificate gateway credentials
            // table, tenant_id is never null here.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->json('credentials')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_payment_gateway_credentials');
    }
};
