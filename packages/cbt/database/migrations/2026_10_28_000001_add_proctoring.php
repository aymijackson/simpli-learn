<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webcam proctoring and supervised exams.
 *
 * Exams: webcam snapshots (on top of the browser monitoring flag), how often
 * to take them, how long to keep them, and two ways to supervise a sitting —
 * an invigilator's start code and a list of allowed networks.
 * Attempts: when the candidate consented, and a reviewer's verdict.
 * Snapshots: the ID check photo and the photos taken during the attempt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('webcam_proctoring')->default(false)->after('integrity_monitoring_enabled');
            $table->unsignedSmallInteger('snapshot_interval_seconds')->default(60)->after('webcam_proctoring');
            $table->unsignedSmallInteger('proctoring_retention_days')->default(90)->after('snapshot_interval_seconds');
            $table->text('invigilator_code')->nullable()->after('proctoring_retention_days');
            $table->text('allowed_networks')->nullable()->after('invigilator_code');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->timestamp('proctoring_consented_at')->nullable();
            $table->string('review_status', 20)->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });

        Schema::create('exam_proctoring_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('event_type')->nullable();
            $table->string('path');
            $table->timestamps();

            $table->index(['exam_attempt_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_proctoring_snapshots');

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['proctoring_consented_at', 'review_status', 'review_notes', 'reviewed_at']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['webcam_proctoring', 'snapshot_interval_seconds', 'proctoring_retention_days', 'invigilator_code', 'allowed_networks']);
        });
    }
};
