<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('answer_type')->default('single')->after('question_text');
            $table->foreignId('exam_section_id')->nullable()->after('exam_id')->constrained('exam_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_section_id');
            $table->dropColumn('answer_type');
        });
    }
};
