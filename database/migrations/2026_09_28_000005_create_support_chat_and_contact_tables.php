<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One conversation per person per desk: "workspace" (answered by the
        // workspace's owners) or "platform" (owners asking the platform admin).
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('desk', 20);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->timestamp('last_message_at')->nullable();
            // The last message each side has seen (ids, so same-second messages are exact).
            $table->unsignedBigInteger('requester_read_id')->default(0);
            $table->unsignedBigInteger('staff_read_id')->default(0);
            $table->timestamp('staff_emailed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'desk']);
            $table->index(['desk', 'tenant_id', 'last_message_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('from_staff')->default(false);
            $table->text('body');
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });

        // Public contact form. Personal details and the message are encrypted
        // at rest; the IP is kept only as a salted hash for abuse checks.
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('email');
            $table->text('subject')->nullable();
            $table->text('message');
            $table->string('ip_hash', 64)->index();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');
    }
};
