<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // An unpacked SCORM package (folder on the private disk) and the
            // page it starts on, relative to that folder.
            $table->string('scorm_path')->nullable()->after('require_video_watch');
            $table->string('scorm_launch', 500)->nullable()->after('scorm_path');
            $table->string('scorm_version', 10)->nullable()->after('scorm_launch');
            // Only the package reporting "completed"/"passed" completes the lesson.
            $table->boolean('scorm_required')->default(true)->after('scorm_version');
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            // The package's saved data (cmi.*), so learners resume where they left off.
            $table->json('scorm_state')->nullable()->after('video_percent');
            $table->decimal('scorm_score', 6, 2)->nullable()->after('scorm_state');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['scorm_state', 'scorm_score']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['scorm_path', 'scorm_launch', 'scorm_version', 'scorm_required']);
        });
    }
};
