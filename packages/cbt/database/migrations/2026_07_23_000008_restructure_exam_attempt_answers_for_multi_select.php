<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('answer_id')->constrained('exam_attempt_answers')->cascadeOnDelete();
            $table->foreignId('question_option_id')->constrained('question_options')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['answer_id', 'question_option_id'], 'attempt_answer_option_unique');
        });

        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selected_option_id');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
        });

        Schema::dropIfExists('exam_attempt_answer_options');
    }
};
