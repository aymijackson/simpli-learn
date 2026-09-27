<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // A YouTube, Vimeo or Bunny Stream link shown as the lesson's player.
            $table->string('video_url', 500)->nullable()->after('content');
            // The lesson can only be completed once most of the video has been watched.
            $table->boolean('require_video_watch')->default(true)->after('video_url');
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            // Share of the video actually watched (seeking ahead doesn't count).
            $table->unsignedTinyInteger('video_percent')->default(0)->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropColumn('video_percent');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'require_video_watch']);
        });
    }
};
