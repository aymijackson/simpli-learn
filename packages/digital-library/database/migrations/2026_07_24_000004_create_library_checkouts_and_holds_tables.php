<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_resources', function (Blueprint $table) {
            // null total_copies = unlimited (checkout still tracked, no scarcity).
            $table->unsignedInteger('total_copies')->nullable()->after('requires_checkout');
            $table->unsignedInteger('checkout_duration_days')->default(14)->after('total_copies');
        });

        Schema::create('library_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('library_resources')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_out_at');
            // MariaDB only allows one non-nullable TIMESTAMP without an
            // explicit default per table (matches exam_attempts'
            // started_at/submitted_at split) — due_at is always set by the
            // application on create, so nullable here is a schema-level
            // accommodation only.
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('library_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('library_resources')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_holds');
        Schema::dropIfExists('library_checkouts');

        Schema::table('library_resources', function (Blueprint $table) {
            $table->dropColumn(['total_copies', 'checkout_duration_days']);
        });
    }
};
