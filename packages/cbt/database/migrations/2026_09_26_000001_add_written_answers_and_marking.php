<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Shown to markers of essay questions.
            $table->text('marking_guide')->nullable()->after('question_text');
        });

        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            // Short-answer and essay responses.
            $table->text('text_response')->nullable()->after('question_id');
            // Manual marking of essays.
            $table->decimal('awarded_points', 8, 2)->nullable()->after('text_response');
            $table->text('feedback')->nullable()->after('awarded_points');
            $table->foreignId('marked_by_user_id')->nullable()->after('feedback')->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable()->after('marked_by_user_id');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            // Submitted, but essay answers still need marking: score stays null until then.
            $table->boolean('needs_marking')->default(false)->after('score')->index();
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex(['needs_marking']);
            $table->dropColumn('needs_marking');
        });

        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marked_by_user_id');
            $table->dropColumn(['text_response', 'awarded_points', 'feedback', 'marked_at']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('marking_guide');
        });
    }
};
